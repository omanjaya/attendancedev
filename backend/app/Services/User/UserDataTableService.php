<?php

namespace App\Services\User;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Facades\DataTables;

class UserDataTableService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Apply role-based filtering to user query
     */
    public function applyRoleBasedFiltering(Builder $query, User $authUser): Builder
    {
        if (!$authUser->hasRole('superadmin')) {
            if ($authUser->hasRole('admin')) {
                // Admin can see teachers and staff, but not other admins or superadmins
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['teacher', 'guru', 'staff', 'pegawai']);
                });
            } elseif ($authUser->hasRole('kepala_sekolah')) {
                // Principal can see users in their school location
                $userLocationId = $authUser->employee?->location_id;
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
                $query->where('id', $authUser->id);
            }
        }

        return $query;
    }

    /**
     * Apply request filters to query
     */
    public function applyRequestFilters(Builder $query, array $filters): Builder
    {
        // Filter by role if specified
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Filter by status if specified
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query;
    }

    /**
     * Get users data for simple JSON response
     */
    public function getUsersForJson(Builder $query): array
    {
        $users = $query->with(['roles', 'employee'])->get();

        return $users->map(function ($user) {
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

                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $color . '">' . ucfirst($role) . '</span>';
                })
                ->implode(' ');

            $employee_info = '';
            if ($user->employee) {
                $employee_info = '<div class="text-success">
                        <small><strong>' . $user->employee->employee_id . '</strong></small><br>
                        <small>' . ucfirst($user->employee->employee_type) . '</small>
                      </div>';
            }

            $status_badge = $user->is_active
              ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success"><div class="w-1.5 h-1.5 rounded-full bg-success mr-1"></div>Active</span>'
              : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-foreground"><div class="w-1.5 h-1.5 rounded-full bg-muted-foreground mr-1"></div>Inactive</span>';

            $canEdit = auth()->user()->can('manage_system_settings');
            $canDelete = auth()->user()->can('manage_system_settings') && $user->id !== auth()->id() && !$user->employee;

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
        })->toArray();
    }

    /**
     * Get users data for DataTables
     */
    public function getUsersForDataTable(Builder $query)
    {
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

                        return '<span class="badge bg-' . $color . '">' . ucfirst($role) . '</span>';
                    })
                    ->implode(' ');

                return $roles ?: '<span class="text-muted">No roles</span>';
            })
            ->addColumn('employee_info', function ($user) {
                if ($user->employee) {
                    return '<div class="text-success">
                        <small><strong>' .
                      $user->employee->employee_id .
                      '</strong></small><br>
                        <small>' .
                      ucfirst($user->employee->employee_type) .
                      '</small>
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
                $canEdit = auth()->user()->can('manage_system_settings');
                $canDelete = auth()->user()->can('manage_system_settings') && $user->id !== auth()->id();

                $actions = '<div class="btn-list">';

                $actions .=
                  '<a href="' .
                  route('users.show', $user) .
                  '" class="btn btn-sm btn-outline-primary">View</a>';

                if ($canEdit) {
                    $actions .=
                      '<a href="' .
                      route('users.edit', $user) .
                      '" class="btn btn-sm btn-outline-warning">Edit</a>';

                    $statusText = $user->is_active ? 'Deactivate' : 'Activate';
                    $statusColor = $user->is_active ? 'orange' : 'green';
                    $actions .=
                      '<button class="btn btn-sm btn-outline-' .
                      $statusColor .
                      ' toggle-status" data-id="' .
                      $user->id .
                      '">' .
                      $statusText .
                      '</button>';
                }

                if ($canDelete && !$user->employee) {
                    $actions .=
                      '<button class="btn btn-sm btn-outline-danger delete-user" data-id="' .
                      $user->id .
                      '">Delete</button>';
                }

                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['roles_list', 'employee_info', 'status_badge', 'actions'])
            ->make(true);
    }
}
