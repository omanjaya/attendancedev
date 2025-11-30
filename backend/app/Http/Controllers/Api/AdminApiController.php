<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Location;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminApiController extends BaseApiController
{
    // Users
    public function users(Request $request)
    {
        $query = User::query()->with(['roles', 'employee:id,user_id,full_name,employee_id']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('name');

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return $this->paginatedResponse($users, 'Users retrieved');
    }

    public function userStatistics()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'by_role' => Role::withCount('users')->get()->pluck('users_count', 'name'),
        ];

        return $this->apiResponse($stats, 'User statistics retrieved');
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->assignRole($validated['role']);

        return $this->apiResponse($user->load('roles'), 'User created', 201);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'role' => 'sometimes|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['role'])) {
            $role = $validated['role'];
            unset($validated['role']);
            $user->syncRoles([$role]);
        }

        $user->update($validated);

        return $this->apiResponse($user->fresh()->load('roles'), 'User updated');
    }

    public function destroyUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $user->delete();

        return $this->apiResponse(null, 'User deleted');
    }

    public function toggleUserStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $user->update(['is_active' => !$user->is_active]);

        return $this->apiResponse($user->fresh(), 'User status toggled');
    }

    public function roles()
    {
        $roles = Role::all();

        return $this->apiResponse($roles, 'Roles retrieved');
    }

    // Locations
    public function locations(Request $request)
    {
        $query = Location::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('name');

        $perPage = $request->get('per_page', 15);
        $locations = $query->paginate($perPage);

        return $this->paginatedResponse($locations, 'Locations retrieved');
    }

    public function locationStatistics()
    {
        $stats = [
            'total' => Location::count(),
            'active' => Location::where('is_active', true)->count(),
            'inactive' => Location::where('is_active', false)->count(),
        ];

        return $this->apiResponse($stats, 'Location statistics retrieved');
    }

    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer|min:1',
        ]);

        $location = Location::create(array_merge($validated, ['is_active' => true]));

        return $this->apiResponse($location, 'Location created', 201);
    }

    public function updateLocation(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return $this->errorResponse('Location not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'radius' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        $location->update($validated);

        return $this->apiResponse($location->fresh(), 'Location updated');
    }

    public function destroyLocation($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return $this->errorResponse('Location not found', 404);
        }

        $location->delete();

        return $this->apiResponse(null, 'Location deleted');
    }

    // Holidays
    public function holidays(Request $request)
    {
        $query = Holiday::query();

        if ($year = $request->get('year')) {
            $query->whereYear('date', $year);
        }

        if ($month = $request->get('month')) {
            $query->whereMonth('date', $month);
        }

        $query->orderBy('date');

        $perPage = $request->get('per_page', 15);
        $holidays = $query->paginate($perPage);

        return $this->paginatedResponse($holidays, 'Holidays retrieved');
    }

    public function holidayStatistics()
    {
        $currentYear = now()->year;

        $stats = [
            'total_this_year' => Holiday::whereYear('date', $currentYear)->count(),
            'upcoming' => Holiday::where('date', '>=', now())->whereYear('date', $currentYear)->count(),
            'passed' => Holiday::where('date', '<', now())->whereYear('date', $currentYear)->count(),
        ];

        return $this->apiResponse($stats, 'Holiday statistics retrieved');
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        $holiday = Holiday::create($validated);

        return $this->apiResponse($holiday, 'Holiday created', 201);
    }

    public function updateHoliday(Request $request, $id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'description' => 'nullable|string',
            'is_recurring' => 'boolean',
        ]);

        $holiday->update($validated);

        return $this->apiResponse($holiday->fresh(), 'Holiday updated');
    }

    public function destroyHoliday($id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return $this->errorResponse('Holiday not found', 404);
        }

        $holiday->delete();

        return $this->apiResponse(null, 'Holiday deleted');
    }
}
