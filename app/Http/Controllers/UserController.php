<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    use ApiResponseTrait;

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Display users management interface.
     */
    public function index()
    {
        $roles = Role::all();

        // Get statistics for the stats cards
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'users_with_employees' => User::whereHas('employee')->count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];

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
            $user = $this->userRepository->createWithRole($validated, $validated['roles']);

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
            $user = $this->userRepository->updateWithRoles($user->id, $validated, $validated['roles']);

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
            // Check if user has an employee record
            if ($user->employee) {
                return $this->errorResponse('Cannot delete user with associated employee record. Deactivate instead.');
            }

            // Check if it's the current user
            if ($user->id === auth()->id()) {
                return $this->errorResponse('Cannot delete your own account.');
            }

            $user->delete();

            return $this->deletedResponse('User deleted successfully');
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
            // Check if it's the current user
            if ($user->id === auth()->id()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Cannot deactivate your own account.',
                    ],
                    400,
                );
            }

            $user->update(['is_active' => ! $user->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => [
                    'is_active' => $user->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Status update failed: '.$e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get users data for DataTables.
     */
    public function getData(Request $request)
    {
        $query = $this->userRepository->getUsersForDataTable();

        // Apply role-based filtering FIRST
        $user = auth()->user();
        if (! $user->hasRole('superadmin')) {
            if ($user->hasRole('admin')) {
                // Admin can see teachers and staff, but not other admins or superadmins
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['teacher', 'guru', 'staff', 'pegawai']);
                });
            } elseif ($user->hasRole('kepala_sekolah')) {
                // Principal can see users in their school location
                $userLocationId = $user->employee?->location_id;
                if ($userLocationId) {
                    $query->whereHas('employee', function ($q) use ($userLocationId) {
                        $q->where('location_id', $userLocationId);
                    })->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['teacher', 'guru', 'staff', 'pegawai']);
                    });
                } else {
                    // If no location assigned, see no data
                    $query->whereRaw('1 = 0');
                }
            } else {
                // Teachers and staff can only see themselves
                $query->where('id', $user->id);
            }
        }

        $query->orderBy('created_at', 'desc');

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

        // For simple JSON response instead of DataTables
        if (! $request->has('draw')) {
            $users = $query->with(['roles', 'employee'])->get();

            $data = $users->map(function ($user) {
                $roles = $user->roles
                    ->pluck('name')
                    ->map(function ($role) {
                        $colors = [
                            'superadmin' => 'bg-destructive/10 text-destructive',
                            'admin' => 'bg-warning/10 text-warning',
                            'teacher' => 'bg-success/10 text-success',
                            'staff' => 'bg-info/10 text-info',
                        ];
                        $color = $colors[$role] ?? 'bg-secondary/10 text-secondary';

                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium '.$color.'">'.ucfirst($role).'</span>';
                    })
                    ->implode(' ');

                $employee_info = '';
                if ($user->employee) {
                    $employee_info = '<div class="text-success">
                            <small><strong>'.$user->employee->employee_id.'</strong></small><br>
                            <small>'.ucfirst($user->employee->employee_type).'</small>
                          </div>';
                }

                $status_badge = $user->is_active
                  ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success"><div class="w-1.5 h-1.5 rounded-full bg-success mr-1"></div>Active</span>'
                  : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground"><div class="w-1.5 h-1.5 rounded-full bg-muted-foreground mr-1"></div>Inactive</span>';

                $canEdit = auth()->user()->can('manage_system_settings');
                $canDelete = auth()->user()->can('manage_system_settings') && $user->id !== auth()->id() && ! $user->employee;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles_list' => $roles ?: '<span class="text-muted-foreground">No roles</span>',
                    'employee_info' => $employee_info ?: '<span class="text-muted-foreground">No employee record</span>',
                    'status_badge' => $status_badge,
                    'last_login' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never',
                    'can_edit' => $canEdit,
                    'can_delete' => $canDelete,
                    'is_active' => $user->is_active,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return DataTables::of($query)
            ->addColumn('roles_list', function ($user) {
                $roles = $user->roles
                    ->pluck('name')
                    ->map(function ($role) {
                        $colors = [
                            'superadmin' => 'danger',
                            'admin' => 'warning',
                            'teacher' => 'success',
                            'staff' => 'info',
                        ];
                        $color = $colors[$role] ?? 'secondary';

                        return '<span class="badge bg-'.$color.'">'.ucfirst($role).'</span>';
                    })
                    ->implode(' ');

                return $roles ?: '<span class="text-muted">No roles</span>';
            })
            ->addColumn('employee_info', function ($user) {
                if ($user->employee) {
                    return '<div class="text-success">
                        <small><strong>'.
                      $user->employee->employee_id.
                      '</strong></small><br>
                        <small>'.
                      ucfirst($user->employee->employee_type).
                      '</small>
                    </div>';
                }

                return '<span class="text-muted">No employee record</span>';
            })
            ->addColumn('status_badge', function ($user) {
                $color = $user->is_active ? 'success' : 'danger';
                $text = $user->is_active ? 'Active' : 'Inactive';

                return '<span class="badge bg-'.$color.'">'.$text.'</span>';
            })
            ->addColumn('last_login', function ($user) {
                return $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never';
            })
            ->addColumn('actions', function ($user) {
                $canEdit = auth()->user()->can('manage_system_settings');
                $canDelete = auth()->user()->can('manage_system_settings') && $user->id !== auth()->id();

                $actions = '<div class="btn-list">';

                $actions .=
                  '<a href="'.
                  route('users.show', $user).
                  '" class="btn btn-sm btn-outline-primary">View</a>';

                if ($canEdit) {
                    $actions .=
                      '<a href="'.
                      route('users.edit', $user).
                      '" class="btn btn-sm btn-outline-warning">Edit</a>';

                    $statusText = $user->is_active ? 'Deactivate' : 'Activate';
                    $statusColor = $user->is_active ? 'orange' : 'green';
                    $actions .=
                      '<button class="btn btn-sm btn-outline-'.
                      $statusColor.
                      ' toggle-status" data-id="'.
                      $user->id.
                      '">'.
                      $statusText.
                      '</button>';
                }

                if ($canDelete && ! $user->employee) {
                    $actions .=
                      '<button class="btn btn-sm btn-outline-danger delete-user" data-id="'.
                      $user->id.
                      '">Delete</button>';
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
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Failed to get statistics: '.$e->getMessage(),
                ],
                500,
            );
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
            $user->update([
                'password' => Hash::make($validated['new_password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Password reset failed: '.$e->getMessage(),
                ],
                500,
            );
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
                    'text' => $user->name.' ('.$user->email.')',
                ];
            });

        return response()->json($users);
    }
}
