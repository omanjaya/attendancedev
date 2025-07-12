<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    /**
     * Display users management interface.
     */
    public function index()
    {
        $roles = Role::all();
        return view('pages.users.index', compact('roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = Role::all();
        return view('pages.users.create', compact('roles'));
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
            'is_active' => ['boolean']
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Assign roles
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $user->assignRole($roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'User creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['roles', 'permissions', 'employee']);
        return view('pages.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load(['roles']);
        $roles = Role::all();
        return view('pages.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['boolean']
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            // Only update password if provided
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Update roles
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $user->syncRoles($roles);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'User update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        try {
            // Check if user has an employee record
            if ($user->employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete user with associated employee record. Deactivate instead.'
                ], 400);
            }

            // Check if it's the current user
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account.'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        try {
            // Check if it's the current user
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate your own account.'
                ], 400);
            }

            $user->update(['is_active' => !$user->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => [
                    'is_active' => $user->is_active
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Status update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users data for DataTables.
     */
    public function getData(Request $request)
    {
        $query = User::with(['roles', 'employee'])
                    ->withCount(['roles'])
                    ->orderBy('created_at', 'desc');

        // Filter by role if specified
        if ($request->has('role') && $request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by status if specified
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        return DataTables::of($query)
            ->addColumn('roles_list', function ($user) {
                $roles = $user->roles->pluck('name')->map(function ($role) {
                    $colors = [
                        'superadmin' => 'danger',
                        'admin' => 'warning', 
                        'teacher' => 'success',
                        'staff' => 'info'
                    ];
                    $color = $colors[$role] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($role) . '</span>';
                })->implode(' ');
                
                return $roles ?: '<span class="text-muted">No roles</span>';
            })
            ->addColumn('employee_info', function ($user) {
                if ($user->employee) {
                    return '<div class="text-success">
                        <small><strong>' . $user->employee->employee_id . '</strong></small><br>
                        <small>' . ucfirst($user->employee->employee_type) . '</small>
                    </div>';
                }
                return '<span class="text-muted">No employee record</span>';
            })
            ->addColumn('status_badge', function ($user) {
                $color = $user->is_active ? 'success' : 'danger';
                $text = $user->is_active ? 'Active' : 'Inactive';
                return '<span class="badge bg-' . $color . '">' . $text . '</span>';
            })
            ->addColumn('last_login', function ($user) {
                return $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never';
            })
            ->addColumn('actions', function ($user) {
                $canEdit = auth()->user()->can('manage_system');
                $canDelete = auth()->user()->can('manage_system') && $user->id !== auth()->id();
                
                $actions = '<div class="btn-list">';
                
                $actions .= '<a href="' . route('users.show', $user) . '" class="btn btn-sm btn-outline-primary">View</a>';
                
                if ($canEdit) {
                    $actions .= '<a href="' . route('users.edit', $user) . '" class="btn btn-sm btn-outline-warning">Edit</a>';
                    
                    $statusText = $user->is_active ? 'Deactivate' : 'Activate';
                    $statusColor = $user->is_active ? 'orange' : 'green';
                    $actions .= '<button class="btn btn-sm btn-outline-' . $statusColor . ' toggle-status" data-id="' . $user->id . '">' . $statusText . '</button>';
                }
                
                if ($canDelete && !$user->employee) {
                    $actions .= '<button class="btn btn-sm btn-outline-danger delete-user" data-id="' . $user->id . '">Delete</button>';
                }
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['roles_list', 'employee_info', 'status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Get user statistics.
     */
    public function getStatistics()
    {
        try {
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $inactiveUsers = User::where('is_active', false)->count();
            $usersWithEmployees = User::whereHas('employee')->count();
            
            // Role distribution
            $roleDistribution = [];
            $roles = Role::withCount('users')->get();
            foreach ($roles as $role) {
                $roleDistribution[$role->name] = $role->users_count;
            }

            $statistics = [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'users_with_employees' => $usersWithEmployees,
                'role_distribution' => $roleDistribution,
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()]
        ]);

        try {
            $user->update([
                'password' => Hash::make($validated['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password reset failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users for dropdown/select.
     */
    public function getUsersForSelect()
    {
        $users = User::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email'])
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'text' => $user->name . ' (' . $user->email . ')'
                        ];
                    });

        return response()->json($users);
    }
}