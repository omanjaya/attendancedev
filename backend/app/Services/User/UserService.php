<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Create a new user with roles
     */
    public function createUser(array $data, array $roleIds): User
    {
        return $this->userRepository->createWithRole($data, $roleIds);
    }

    /**
     * Update user with roles
     */
    public function updateUser(int $userId, array $data, array $roleIds): User
    {
        return $this->userRepository->updateWithRoles($userId, $data, $roleIds);
    }

    /**
     * Delete user (with safety checks)
     */
    public function deleteUser(User $user, int $currentUserId): array
    {
        // Check if user has an employee record
        if ($user->employee) {
            return [
                'success' => false,
                'message' => 'Cannot delete user with associated employee record. Deactivate instead.'
            ];
        }

        // Check if it's the current user
        if ($user->id === $currentUserId) {
            return [
                'success' => false,
                'message' => 'Cannot delete your own account.'
            ];
        }

        $user->delete();

        return [
            'success' => true,
            'message' => 'User deleted successfully'
        ];
    }

    /**
     * Toggle user active status
     */
    public function toggleUserStatus(User $user, int $currentUserId): array
    {
        // Check if it's the current user
        if ($user->id === $currentUserId) {
            return [
                'success' => false,
                'message' => 'Cannot deactivate your own account.'
            ];
        }

        $user->update(['is_active' => !$user->is_active]);

        return [
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'is_active' => $user->is_active,
            ]
        ];
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user, string $newPassword): bool
    {
        return $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Get users for select dropdown
     */
    public function getUsersForSelect(): array
    {
        return User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name . ' (' . $user->email . ')',
                ];
            })
            ->toArray();
    }
}
