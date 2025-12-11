<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\User\UserService;
use App\Services\User\UserStatisticsService;
use App\Services\User\UserDataTableService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private UserRepository $userRepository,
        private UserService $userService,
        private UserStatisticsService $statisticsService,
        private UserDataTableService $dataTableService
    ) {}

    /**
     * Display users management interface.
     */
    public function index()
    {
        $roles = Role::all();
        $stats = $this->statisticsService->getIndexStats();

        return view('pages.management.users.index', compact('roles', 'stats'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();

        return view('pages.management.users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        try {
            $user = $this->userService->createUser($validated, $validated['roles']);

            return $this->createdResponse([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ], 'User created successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('User creation failed: '.$e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'permissions', 'employee']);

        return view('pages.management.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load(['roles']);
        $roles = Role::all();

        return view('pages.management.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        try {
            $user = $this->userService->updateUser($user->id, $validated, $validated['roles']);

            return $this->updatedResponse(null, 'User updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('User update failed: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        try {
            $result = $this->userService->deleteUser($user, auth()->id());

            if (!$result['success']) {
                return $this->errorResponse($result['message']);
            }

            return $this->deletedResponse($result['message']);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('User deletion failed: '.$e->getMessage());
        }
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        try {
            $result = $this->userService->toggleUserStatus($user, auth()->id());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Status update failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users data for DataTables.
     */
    public function getData(Request $request)
    {
        $query = $this->userRepository->getUsersForDataTable();

        // Apply role-based filtering
        $query = $this->dataTableService->applyRoleBasedFiltering($query, auth()->user());
        $query->orderBy('created_at', 'desc');

        // Apply request filters
        $filters = $request->only(['role', 'status']);
        $query = $this->dataTableService->applyRequestFilters($query, $filters);

        // For simple JSON response instead of DataTables
        if (!$request->has('draw')) {
            $data = $this->dataTableService->getUsersForJson($query);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return $this->dataTableService->getUsersForDataTable($query);
    }

    /**
     * Get user statistics.
     */
    public function getStatistics()
    {
        try {
            $statistics = $this->statisticsService->getStatistics();

            return response()->json([
                'success' => true,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        try {
            $this->userService->resetPassword($user, $validated['new_password']);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users for dropdown/select.
     */
    public function getUsersForSelect()
    {
        $users = $this->userService->getUsersForSelect();
        return response()->json($users);
    }
}
