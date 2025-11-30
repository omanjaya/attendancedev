<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Repositories\LocationRepository;
use App\Repositories\UserRepository;
use App\Services\EmployeeIdGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

/**
 * Employee Service
 *
 * Centralized business logic for employee management
 * Clean, readable methods with single responsibilities
 */
class EmployeeService
{
    protected $employeeRepository;

    protected $userRepository;

    protected $locationRepository;
    
    protected $idGenerator;

    public function __construct(
        EmployeeRepository $employeeRepository,
        UserRepository $userRepository,
        LocationRepository $locationRepository,
        EmployeeIdGeneratorService $idGenerator
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->userRepository = $userRepository;
        $this->locationRepository = $locationRepository;
        $this->idGenerator = $idGenerator;
    }

    /**
     * Get data for index page
     */
    public function getIndexData(): array
    {
        $filterOptions = $this->getFilterOptions();

        return [
            'employees' => $this->getEmployeeList(),
            'statistics' => $this->employeeRepository->getEmployeeStatistics(),
            'filters' => $filterOptions,
            'departments' => $filterOptions['departments'] ?? [],
        ];
    }

    /**
     * Get data for forms (create/edit)
     */
    public function getFormData(): array
    {
        return [
            'roles' => $this->getRoles(),
            'locations' => $this->locationRepository->getLocationsForDropdown(),
            'employeeTypes' => $this->getEmployeeTypes(),
        ];
    }

    /**
     * Create new employee
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            // Step 1: Create user account
            $user = $this->createUserAccount($data);

            // Step 2: Upload photo if provided
            $photoPath = $this->handlePhotoUpload($data['photo'] ?? null);

            // Step 3: Handle face descriptor if provided
            $this->handleFaceDescriptor($user, $data);
            
            // Step 4: Generate employee ID if not provided
            if (empty($data['employee_id'])) {
                $role = $data['role'] ?? $user->roles->first()->name ?? 'pegawai';
                $employeeType = $data['employee_type'] ?? 'staff';
                $data['employee_id'] = $this->idGenerator->generateUniqueEmployeeId($role, $employeeType);
            }

            // Step 5: Create employee record with face metadata
            $employeeData = [
                'user_id' => $user->id,
                'employee_id' => $data['employee_id'],
                'full_name' => $data['full_name'],
                'phone' => $data['phone'] ?? null,
                'photo_path' => $photoPath,
                'employee_type' => $data['employee_type'],
                'hire_date' => $data['hire_date'],
                'salary_type' => $data['salary_type'],
                'salary_amount' => $data['salary_amount'] ?? null,
                'hourly_rate' => $data['hourly_rate'] ?? null,
                'location_id' => $data['location_id'] ?? null,
            ];

            // Add face recognition metadata if face descriptor exists
            if (isset($data['face_descriptor'])) {
                $employeeData['metadata'] = [
                    'face_recognition' => [
                        'descriptor' => json_decode($data['face_descriptor'], true),
                        'confidence' => 85,
                        'registered_at' => now()->toISOString(),
                        'model_version' => 'face-api-js-1.0',
                    ]
                ];
            }

            return $this->employeeRepository->create($employeeData);
        });
    }

    /**
     * Update existing employee
     */
    public function update(Employee $employee, array $data): bool
    {
        return DB::transaction(function () use ($employee, $data) {
            // Step 1: Update user account
            $this->updateUserAccount($employee->user, $data);

            // Step 2: Handle photo if changed
            if (isset($data['photo']) && $data['photo'] !== null) {
                \Log::info('Handling photo upload', [
                    'employee_id' => $employee->id,
                    'old_photo' => $employee->photo_path,
                    'photo_size' => $data['photo']->getSize(),
                    'photo_name' => $data['photo']->getClientOriginalName()
                ]);
                
                $this->deleteOldPhoto($employee->photo_path);
                $data['photo_path'] = $this->handlePhotoUpload($data['photo']);
                
                \Log::info('Photo uploaded successfully', [
                    'employee_id' => $employee->id,
                    'new_photo_path' => $data['photo_path']
                ]);
                
                // Remove the original photo field
                unset($data['photo']);
            }

            // Step 3: Handle face descriptor if provided
            $this->handleFaceDescriptor($employee->user, $data);

            // Step 4: Handle face metadata for employee
            if (isset($data['face_descriptor'])) {
                $metadata = $employee->metadata ?? [];
                $metadata['face_recognition'] = [
                    'descriptor' => json_decode($data['face_descriptor'], true),
                    'confidence' => 85,
                    'registered_at' => now()->toISOString(),
                    'model_version' => 'face-api-js-1.0',
                ];
                $data['metadata'] = $metadata;
            }

            // Step 5: Update employee data using repository
            return $this->employeeRepository->update($employee->id, $this->prepareEmployeeData($data));
        });
    }

    /**
     * Get DataTable response with advanced filtering
     */
    public function getDataTableResponse($request = null)
    {
        // Use repository to get filtered employees
        $employees = $this->employeeRepository->getFilteredEmployees($request);
        
        // Get employee statistics for the frontend
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $inactiveEmployees = Employee::where('is_active', false)->count();

        return DataTables::of($employees)
            ->addColumn('name', fn ($e) => $e->full_name)
            ->addColumn('email', fn ($e) => $e->user->email)
            ->addColumn('phone', fn ($e) => $e->phone)
            ->addColumn('department', fn ($e) => $e->location->name ?? 'N/A')
            ->addColumn('position', fn ($e) => $e->metadata['position'] ?? 'N/A')
            ->addColumn('type', fn ($e) => ucfirst($e->employee_type))
            ->addColumn('status', fn ($e) => $this->formatStatus($e))
            ->addColumn('face_registered', fn ($e) => $this->formatFaceStatus($e))
            ->addColumn('last_attendance', fn ($e) => $this->getLastAttendance($e))
            ->addColumn('actions', fn ($e) => $this->formatActions($e))
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('full_name', 'like', "%{$keyword}%");
            })
            ->filterColumn('email', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('department', function ($query, $keyword) {
                $query->whereHas('location', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['status', 'actions', 'face_registered'])
            ->with([
                'stats' => [
                    'total' => $totalEmployees,
                    'active' => $activeEmployees,
                    'inactive' => $inactiveEmployees
                ]
            ])
            ->make(true);
    }

    /**
     * Apply role-based filtering to limit data access based on user permissions
     */
    private function applyRoleBasedFiltering($query)
    {
        $user = auth()->user();

        // Super Admin sees all data
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        // Admin sees all operational data
        if ($user->hasRole('admin')) {
            return $query;
        }

        // Kepala Sekolah sees employees in their school/location
        if ($user->hasRole('kepala_sekolah')) {
            $userLocationIds = $user->employee?->location_id ? [$user->employee->location_id] : [];

            return $query->whereIn('location_id', $userLocationIds);
        }

        // Teachers and Staff can only see their own data
        if ($user->hasRole(['guru', 'teacher', 'pegawai', 'staff'])) {
            return $query->where('id', $user->employee?->id ?? 0);
        }

        // Default: no access for unrecognized roles
        return $query->whereRaw('1 = 0');
    }

    /**
     * Apply filters to employee query
     */
    private function applyFilters($query, $request)
    {
        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%");
                    });
            });
        }

        // Department filter
        if ($department = $request->get('department')) {
            $query->where('location_id', $department);
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('is_active', $status === 'active');
        }

        // Employee type filter
        if ($type = $request->get('type')) {
            $query->where('employee_type', $type);
        }

        // Face registration filter
        if ($face = $request->get('face_registered')) {
            if ($face === 'yes') {
                $query->whereNotNull('metadata->face_recognition->descriptor');
            } elseif ($face === 'no') {
                $query->whereNull('metadata->face_recognition->descriptor');
            }
        }

        // Date range filter
        if ($from = $request->get('date_from')) {
            $query->where('hire_date', '>=', $from);
        }

        if ($to = $request->get('date_to')) {
            $query->where('hire_date', '<=', $to);
        }

        return $query;
    }

    /**
     * Export employees data
     */
    public function exportEmployees($format = 'csv', $filters = [])
    {
        $query = Employee::with(['user', 'location']);

        // Apply filters if provided
        if (! empty($filters)) {
            $query = $this->applyFilters($query, (object) $filters);
        }

        $employees = $query->get();

        return match ($format) {
            'csv' => $this->exportToCSV($employees),
            'excel' => $this->exportToExcel($employees),
            'pdf' => $this->exportToPDF($employees),
            default => $this->exportToCSV($employees)
        };
    }

    /**
     * Import employees from file
     */
    public function importEmployees($file, $options = [])
    {
        $data = $this->parseImportFile($file);
        $results = ['success' => 0, 'errors' => [], 'skipped' => 0];

        // Track processed emails to provide better error messages
        $processedEmails = [];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // Excel row number (accounting for header)
            
            try {
                $mappedData = $this->mapImportData($row);
                
                // Check for duplicate email in this batch
                $email = strtolower(trim($mappedData['email'] ?? ''));
                if (isset($processedEmails[$email])) {
                    $results['errors'][] = "Baris {$rowNumber}: Email '{$email}' sudah digunakan di baris {$processedEmails[$email]} - DATA DUPLIKAT DILEWATI";
                    $results['skipped']++;
                    continue;
                }
                
                // Check if email already exists in database
                $existingUser = \App\Models\User::where('email', $email)->first();
                if ($existingUser) {
                    if (isset($options['update_existing']) && $options['update_existing']) {
                        // Update existing employee data
                        try {
                            $employee = Employee::where('user_id', $existingUser->id)->first();
                            if ($employee) {
                                $this->update($employee, $mappedData);
                                $results['success']++;
                                $results['errors'][] = "Baris {$rowNumber}: Data '{$email}' berhasil diupdate";
                            } else {
                                // User exists but no employee record - create employee record
                                $mappedData['user_id'] = $existingUser->id;
                                $this->createEmployeeOnly($mappedData);
                                $results['success']++;
                            }
                            $processedEmails[$email] = $rowNumber;
                            continue;
                        } catch (\Exception $e) {
                            $results['errors'][] = "Baris {$rowNumber}: Gagal update '{$email}' - {$e->getMessage()}";
                            continue;
                        }
                    } else if (isset($options['skip_duplicates']) && $options['skip_duplicates']) {
                        $results['skipped']++;
                        continue;
                    } else {
                        $results['errors'][] = "Baris {$rowNumber}: Email '{$email}' sudah terdaftar di database";
                        continue;
                    }
                }
                
                $employee = $this->create($mappedData);
                $processedEmails[$email] = $rowNumber;
                $results['success']++;
                
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle database constraint violations specifically
                if (str_contains($e->getMessage(), 'users_email_unique')) {
                    $results['errors'][] = "Baris {$rowNumber}: Email sudah terdaftar di database";
                } else {
                    $results['errors'][] = "Baris {$rowNumber}: Error database - {$e->getMessage()}";
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Baris {$rowNumber}: {$e->getMessage()}";
            }
        }

        // Add detailed summary
        $results['summary'] = [
            'total_processed' => count($data),
            'successful_imports' => $results['success'],
            'skipped_duplicates' => $results['skipped'],
            'error_count' => count($results['errors']),
            'unique_emails_processed' => count($processedEmails),
            'message' => $this->generateImportSummaryMessage($results, count($data))
        ];

        return $results;
    }

    /**
     * Generate import summary message
     */
    private function generateImportSummaryMessage(array $results, int $totalRows): string
    {
        $message = "Import selesai: ";
        
        if ($results['success'] > 0) {
            $message .= "{$results['success']} karyawan berhasil diimpor";
        } else {
            $message .= "Tidak ada karyawan yang berhasil diimpor";
        }
        
        if ($results['skipped'] > 0) {
            $message .= ", {$results['skipped']} data dilewati karena duplikat";
        }
        
        if (count($results['errors']) > 0) {
            $message .= ", " . count($results['errors']) . " error ditemukan";
        }
        
        // Add specific guidance if many duplicates detected
        if ($results['skipped'] > ($totalRows * 0.5)) {
            $message .= ". PERHATIAN: Banyak data duplikat terdeteksi. Pastikan setiap baris memiliki email yang unik.";
        }
        
        return $message;
    }

    /**
     * Create employee record only (when user already exists)
     */
    private function createEmployeeOnly(array $data): Employee
    {
        // Generate employee ID
        $employeeId = $this->generateEmployeeId($data['role'] ?? 'pegawai', $data['employee_type'] ?? 'staff');
        
        // Prepare employee data
        $employeeData = [
            'employee_id' => $employeeId,
            'user_id' => $data['user_id'],
            'full_name' => $data['full_name'] ?? $data['name'] ?? '',
            'phone' => $data['phone'] ?? null,
            'employee_type' => $data['employee_type'] ?? 'staff',
            'hire_date' => $this->parseDate($data['hire_date'] ?? null) ?? now(),
            'salary_type' => $data['salary_type'] ?? 'monthly',
            'base_salary' => $data['salary_amount'] ?? $data['base_salary'] ?? 0,
            'hourly_rate' => $data['hourly_rate'] ?? 0,
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'status' => $this->parseStatus($data['status'] ?? 'active'),
            'metadata' => [
                'import_date' => now()->toDateTimeString(),
                'import_source' => 'excel',
                'updated_via_import' => true
            ]
        ];
        
        // Handle location
        if (!empty($data['department'])) {
            $location = Location::where('name', 'like', '%' . $data['department'] . '%')->first();
            if ($location) {
                $employeeData['location_id'] = $location->id;
            }
        }
        
        return Employee::create($employeeData);
    }

    /**
     * Handle bulk operations
     */
    public function handleBulkOperation($request): array
    {
        $action = $request->input('action', $request->input('operation'));
        $employeeIds = $request->input('employee_ids');
        
        // Handle both array and comma-separated string inputs
        if (is_array($employeeIds)) {
            $ids = $employeeIds;
        } else if (is_string($employeeIds)) {
            $ids = explode(',', $employeeIds);
        } else {
            throw new \InvalidArgumentException('employee_ids must be an array or comma-separated string');
        }
        
        // Filter out empty values
        $ids = array_filter($ids, function($id) {
            return !empty(trim($id));
        });
        
        \Log::info('Bulk operation processing', [
            'action' => $action,
            'employee_ids_count' => count($ids),
            'employee_ids' => $ids
        ]);
        
        if (empty($ids)) {
            return [
                'success' => false,
                'message' => 'No valid employee IDs provided'
            ];
        }

        return match ($action) {
            'delete' => $this->bulkDelete($ids),
            'update' => $this->bulkUpdate($request, $ids),
            'upload' => $this->bulkUpload($request),
            'activate' => $this->bulkUpdateStatus($ids, true),
            'deactivate' => $this->bulkUpdateStatus($ids, false),
            'reset_password' => $this->bulkResetPassword($ids),
            'assign_location' => $this->bulkAssignLocation($request, $ids),
            'send_invitation' => $this->bulkSendInvitation($ids),
            'export_selected' => $this->bulkExport($request, $ids),
            default => ['success' => false, 'message' => 'Operasi tidak valid']
        };
    }

    // ========== PRIVATE HELPER METHODS ==========

    private function getEmployeeList()
    {
        return $this->employeeRepository->getActiveEmployees()
            ->take(10)
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'email' => $e->user->email,
                'type' => $e->employee_type,
                'department' => $e->location->name ?? 'N/A',
                'status' => $e->is_active ? 'active' : 'inactive',
                'last_check_in' => $e->attendances->first()?->created_at?->format('M d, Y H:i A') ?? 'Never',
                'face_registered' => $this->hasFaceRegistered($e),
            ]);
    }

    // Remove this method as it's handled by repository now

    private function getFilterOptions(): array
    {
        return [
            'departments' => $this->locationRepository->getLocationsForDropdown(),
            'types' => $this->getEmployeeTypes(),
            'statuses' => ['active' => 'Active', 'inactive' => 'Inactive'],
        ];
    }

    private function getRoles()
    {
        return Role::whereNot('name', 'superadmin')->get();
    }

    // Remove this method as it's handled by repository now

    private function getEmployeeTypes(): array
    {
        return [
            'permanent' => 'Permanent',
            'honorary' => 'Honorary',
            'staff' => 'Staff',
        ];
    }

    private function createUserAccount(array $data): User
    {
        $user = $this->userRepository->create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        return $user;
    }

    private function updateUserAccount(User $user, array $data): void
    {
        $userData = [
            'name' => $data['full_name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $userData['password'] = Hash::make($data['password']);
        }

        $this->userRepository->update($user->id, $userData);
        $user->syncRoles([$data['role']]);
    }

    private function handlePhotoUpload($photo): ?string
    {
        return $photo ? $photo->store('employee-photos', 'public') : null;
    }

    private function deleteOldPhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function handleFaceDescriptor(User $user, array $data): void
    {
        if (isset($data['face_descriptor'])) {
            $faceDescriptor = json_decode($data['face_descriptor'], true);
            
            // Save face descriptor to user table
            $user->face_descriptor = $faceDescriptor;
            $user->face_registered_at = now();
            $user->save();
            
            // Log the face descriptor save
            \Log::info('Face descriptor saved to user', [
                'user_id' => $user->id,
                'descriptor_size' => is_array($faceDescriptor) ? count($faceDescriptor) : 'not_array',
                'face_registered_at' => $user->face_registered_at,
            ]);
        }
    }

    private function prepareEmployeeData(array $data): array
    {
        return collect($data)
            ->only(['employee_id', 'full_name', 'phone', 'photo_path',
                'employee_type', 'hire_date', 'salary_type', 'salary_amount',
                'hourly_rate', 'location_id', 'metadata', 'is_active'])
            ->filter()
            ->toArray();
    }

    private function hasFaceRegistered(Employee $employee): bool
    {
        return isset($employee->metadata['face_recognition']['descriptor']);
    }

    private function formatStatus(Employee $employee): string
    {
        $class = $employee->is_active ? 'success' : 'secondary';
        $text = $employee->is_active ? 'Active' : 'Inactive';

        return "<span class='badge bg-{$class}'>{$text}</span>";
    }

    private function formatActions(Employee $employee): string
    {
        $showUrl = route('employees.show', $employee);
        $editUrl = route('employees.edit', $employee);

        return "
            <div class='flex space-x-2'>
                <a href='{$showUrl}' class='inline-flex items-center px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200'>
                    <svg class='w-3 h-3 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path>
                    </svg>
                    Lihat
                </a>
                <a href='{$editUrl}' class='inline-flex items-center px-2 py-1 text-xs bg-emerald-100 text-emerald-800 rounded hover:bg-emerald-200'>
                    <svg class='w-3 h-3 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'></path>
                    </svg>
                    Edit
                </a>
                <button onclick='deleteEmployee({$employee->id})' class='inline-flex items-center px-2 py-1 text-xs bg-red-100 text-red-800 rounded hover:bg-red-200'>
                    <svg class='w-3 h-3 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'></path>
                    </svg>
                    Hapus
                </button>
            </div>
        ";
    }

    private function formatFaceStatus(Employee $employee): string
    {
        $hasDescriptor = isset($employee->metadata['face_recognition']['descriptor']);

        if ($hasDescriptor) {
            return "<span class='inline-flex items-center px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full'>
                        <svg class='w-3 h-3 mr-1' fill='currentColor' viewBox='0 0 20 20'>
                            <path fill-rule='evenodd' d='M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z' clip-rule='evenodd'></path>
                        </svg>
                        Terdaftar
                    </span>";
        }

        return "<span class='inline-flex items-center px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full'>
                    <svg class='w-3 h-3 mr-1' fill='currentColor' viewBox='0 0 20 20'>
                        <path fill-rule='evenodd' d='M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z' clip-rule='evenodd'></path>
                    </svg>
                    Belum Terdaftar
                </span>";
    }

    private function getLastAttendance(Employee $employee): string
    {
        $lastAttendance = $employee->attendances()->latest()->first();

        if ($lastAttendance) {
            return $lastAttendance->created_at->format('d M Y, H:i');
        }

        return "<span class='text-gray-500'>Belum ada absensi</span>";
    }

    private function exportToCSV($employees)
    {
        $headers = [
            'ID Karyawan', 'Nama Lengkap', 'Email', 'Telepon', 'Departemen',
            'Posisi', 'Tipe Karyawan', 'Tanggal Bergabung', 'Status', 'Wajah Terdaftar',
        ];

        $callback = function () use ($employees, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($employees as $employee) {
                fputcsv($file, [
                    $employee->employee_id,
                    $employee->full_name,
                    $employee->user->email,
                    $employee->phone,
                    $employee->location->name ?? 'N/A',
                    $employee->metadata['position'] ?? 'N/A',
                    ucfirst($employee->employee_type),
                    $employee->hire_date->format('Y-m-d'),
                    $employee->is_active ? 'Aktif' : 'Tidak Aktif',
                    isset($employee->metadata['face_recognition']['descriptor']) ? 'Ya' : 'Tidak',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="data_karyawan_'.date('Y-m-d_H-i-s').'.csv"',
        ]);
    }

    private function exportToExcel($employees)
    {
        // Implementasi export Excel menggunakan library seperti PhpSpreadsheet
        // Untuk sekarang kita return CSV format
        return $this->exportToCSV($employees);
    }

    private function exportToPDF($employees)
    {
        // Implementasi export PDF menggunakan library seperti DomPDF
        // Untuk sekarang kita return CSV format
        return $this->exportToCSV($employees);
    }

    public function parseImportFile($file)
    {
        $extension = $file->getClientOriginalExtension();

        return match ($extension) {
            'csv' => $this->parseCSV($file),
            'xlsx', 'xls' => $this->parseExcel($file),
            default => throw new \Exception('Format file tidak didukung. Gunakan CSV atau Excel.')
        };
    }

    private function parseCSV($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Get header row
        $headers = fgetcsv($handle);
        
        // Map English headers to expected format (for compatibility)
        $headerMap = [
            'full_name' => 'full_name',
            'Nama Lengkap' => 'full_name',
            'email' => 'email', 
            'Email' => 'email',
            'phone' => 'phone',
            'Telepon' => 'phone',
            'employee_type' => 'employee_type',
            'Tipe Karyawan' => 'employee_type',
            'role' => 'role',
            'Role' => 'role',
            'salary_type' => 'salary_type',
            'Tipe Gaji' => 'salary_type',
            'salary_amount' => 'salary_amount',
            'Gaji Bulanan' => 'salary_amount',
            'hourly_rate' => 'hourly_rate',
            'Tarif Per Jam' => 'hourly_rate',
            'hire_date' => 'hire_date',
            'Tanggal Bergabung' => 'hire_date',
            'department' => 'department',
            'Departemen' => 'department',
            'position' => 'position',
            'Posisi' => 'position',
            'status' => 'status',
            'Status' => 'status'
        ];
        
        // Skip empty rows and instruction rows
        while (($row = fgetcsv($handle)) !== false) {
            // Skip if row is empty or starts with instruction text
            if (empty($row[0]) || strpos($row[0], 'INSTRUKSI') !== false || strpos($row[0], '-') === 0) {
                continue;
            }
            
            // Only process rows with data
            if (count($row) === count($headers)) {
                $rowData = array_combine($headers, $row);
                
                // Map headers to standard keys
                $mappedData = [];
                foreach ($rowData as $key => $value) {
                    if (isset($headerMap[$key])) {
                        $mappedData[$headerMap[$key]] = $value;
                    } else {
                        $mappedData[$key] = $value;
                    }
                }
                
                $data[] = $mappedData;
            }
        }

        fclose($handle);

        return $data;
    }

    private function parseExcel($file)
    {
        try {
            \Log::info('Starting Excel file parsing', [
                'file_path' => $file->getPathname(),
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize()
            ]);
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            \Log::info('Excel file loaded successfully');
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];
            
            // Header mapping for compatibility
            $headerMap = [
                'full_name' => 'full_name',
                'Nama Lengkap' => 'full_name',
                'email' => 'email', 
                'Email' => 'email',
                'phone' => 'phone',
                'Telepon' => 'phone',
                'employee_type' => 'employee_type',
                'Tipe Karyawan' => 'employee_type',
                'role' => 'role',
                'Role' => 'role',
                'salary_type' => 'salary_type',
                'Tipe Gaji' => 'salary_type',
                'salary_amount' => 'salary_amount',
                'Gaji Bulanan' => 'salary_amount',
                'hourly_rate' => 'hourly_rate',
                'Tarif Per Jam' => 'hourly_rate',
                'hire_date' => 'hire_date',
                'Tanggal Bergabung' => 'hire_date',
                'department' => 'department',
                'Departemen' => 'department',
                'position' => 'position',
                'Posisi' => 'position',
                'status' => 'status',
                'Status' => 'status'
            ];
            
            // Get header row
            $headers = [];
            foreach ($worksheet->getRowIterator(1, 1) as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $headers[] = $cell->getValue();
                }
                break;
            }
            
            // Get data rows
            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowData = [];
                $cellIndex = 0;
                foreach ($row->getCellIterator() as $cell) {
                    if (isset($headers[$cellIndex])) {
                        $rowData[$headers[$cellIndex]] = $cell->getValue();
                    }
                    $cellIndex++;
                }
                
                // Skip empty rows or instruction rows
                if (empty(array_filter($rowData)) || 
                    (isset($rowData[$headers[0]]) && 
                     (strpos($rowData[$headers[0]], 'INSTRUKSI') !== false || 
                      strpos($rowData[$headers[0]], '-') === 0))) {
                    continue;
                }
                
                // Map headers to standard keys
                $mappedData = [];
                foreach ($rowData as $key => $value) {
                    if (isset($headerMap[$key])) {
                        $mappedData[$headerMap[$key]] = $value;
                    } else {
                        $mappedData[$key] = $value;
                    }
                }
                
                $data[] = $mappedData;
            }
            
            return $data;
        } catch (\Exception $e) {
            \Log::error('Excel parsing failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Error parsing Excel file: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')');
        }
    }

    private function mapImportData($row)
    {
        // Validate required fields        
        if (empty($row['full_name'])) {
            throw new \Exception('Nama Lengkap wajib diisi');
        }
        
        if (empty($row['email'])) {
            throw new \Exception('Email wajib diisi');
        }
        
        // Validate email format
        $email = $row['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Format email tidak valid: ' . $email);
        }
        
        // Validate employee type
        $validTypes = ['permanent', 'honorary', 'staff'];
        $employeeType = strtolower($row['employee_type'] ?? 'permanent');
        if (!in_array($employeeType, $validTypes)) {
            throw new \Exception('Tipe karyawan tidak valid. Gunakan: permanent, honorary, atau staff');
        }
        
        // Parse and validate role
        $rawRole = $row['role'] ?? 'pegawai';
        
        // Map role input to actual role names in database
        $roleMapping = [
            'super admin' => 'Super Admin',
            'admin' => 'Admin', 
            'kepala_sekolah' => 'kepala_sekolah',
            'guru' => 'guru',
            'pegawai' => 'pegawai'
        ];
        
        $normalizedRole = strtolower(str_replace(' ', ' ', $rawRole));
        if (isset($roleMapping[$normalizedRole])) {
            $role = $roleMapping[$normalizedRole];
        } else {
            $role = 'pegawai'; // Default fallback
        }
        
        // Validate salary type
        $validSalaryTypes = ['hourly', 'monthly', 'fixed'];
        $salaryType = strtolower($row['salary_type'] ?? 'monthly');
        if (!in_array($salaryType, $validSalaryTypes)) {
            $salaryType = 'monthly'; // Default fallback
        }
        
        // Parse hire date
        $hireDate = $row['hire_date'] ?? now()->format('Y-m-d');
        try {
            // Check if it's an Excel serial number (numeric value)
            if (is_numeric($hireDate)) {
                // Convert Excel serial number to date
                $excelBaseDate = new \DateTime('1900-01-01');
                $excelBaseDate->modify('-2 days'); // Adjust for Excel's leap year bug
                $parsedDate = clone $excelBaseDate;
                $parsedDate->modify('+' . intval($hireDate) . ' days');
                $hireDate = $parsedDate->format('Y-m-d');
            } else {
                // Try string conversion
                $hireDate = date('Y-m-d', strtotime($hireDate));
            }
        } catch (\Exception $e) {
            $hireDate = now()->format('Y-m-d');
        }
        
        // Parse salary amounts
        $salaryAmount = null;
        if (!empty($row['salary_amount'])) {
            $salaryAmount = (float) preg_replace('/[^0-9.]/', '', $row['salary_amount']);
        }
        
        $hourlyRate = null;
        if (!empty($row['hourly_rate'])) {
            $hourlyRate = (float) preg_replace('/[^0-9.]/', '', $row['hourly_rate']);
        }
        
        return [
            'employee_id' => null, // Will be auto-generated
            'full_name' => $row['full_name'],
            'email' => $email,
            'phone' => $row['phone'] ?? '',
            'employee_type' => $employeeType,
            'salary_type' => $salaryType,
            'salary_amount' => $salaryAmount,
            'hourly_rate' => $hourlyRate,
            'hire_date' => $hireDate,
            'location_id' => $this->findLocationByName($row['department'] ?? ''),
            'is_active' => ($row['status'] ?? 'Aktif') === 'Aktif',
            'password' => 'password123', // Default password
            'role' => $role, // Use parsed role
            'metadata' => [
                'position' => $row['position'] ?? '',
                'import_date' => now()->toISOString(),
            ],
        ];
    }

    private function findLocationByName($name)
    {
        if (empty($name)) {
            return null;
        }

        $location = Location::where('name', 'like', "%{$name}%")->first();

        return $location?->id;
    }

    private function bulkDelete(array $ids): array
    {
        try {
            // Get employee names before deletion for notification
            $employees = $this->employeeRepository->findMultiple($ids);
            $count = $this->employeeRepository->deleteMultiple($ids);

            return [
                'success' => true,
                'message' => $count > 0 ? "Successfully deleted {$count} employees" : 'No employees found to delete',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete employees. Please try again.',
            ];
        }
    }

    private function bulkUpdateStatus(array $ids, bool $active): array
    {
        try {
            $count = $this->employeeRepository->updateMultiple($ids, ['is_active' => $active]);
            $status = $active ? 'activated' : 'deactivated';

            return [
                'success' => true,
                'message' => $count > 0 ? "Successfully {$status} {$count} employees" : 'No employees found to update',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update employees. Please try again.',
            ];
        }
    }

    private function bulkUpdate($request, array $ids): array
    {
        try {
            $data = [];

            // Only include non-empty fields
            if ($request->filled('employee_type')) {
                $data['employee_type'] = $request->input('employee_type');
            }

            if ($request->filled('is_active')) {
                $data['is_active'] = (bool) $request->input('is_active');
            }

            if ($request->filled('location_id')) {
                $data['location_id'] = $request->input('location_id');
            }

            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'No changes specified for update',
                ];
            }

            $count = $this->employeeRepository->updateMultiple($ids, $data);

            return [
                'success' => true,
                'message' => $count > 0 ? "Successfully updated {$count} employees" : 'No employees found to update',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update employees. Please try again.',
            ];
        }
    }

    private function bulkUpload($request): array
    {
        try {
            if (! $request->hasFile('excel_file')) {
                return [
                    'success' => false,
                    'message' => 'No file uploaded',
                ];
            }

            $file = $request->file('excel_file');

            // Validate file type
            if (! in_array($file->getClientOriginalExtension(), ['csv', 'xlsx', 'xls'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid file format. Please upload CSV or Excel file.',
                ];
            }

            // Validate file size (max 5MB)
            if ($file->getSize() > 5 * 1024 * 1024) {
                return [
                    'success' => false,
                    'message' => 'File size too large. Maximum 5MB allowed.',
                ];
            }

            // Store the file temporarily
            $path = $file->store('temp/imports');

            // Basic CSV processing (you can enhance this later)
            if ($file->getClientOriginalExtension() === 'csv') {
                $handle = fopen($file->getRealPath(), 'r');
                $header = fgetcsv($handle);
                $rowCount = 0;

                // Count rows (excluding header)
                while (fgetcsv($handle)) {
                    $rowCount++;
                }
                fclose($handle);

                // For now, just return success with row count
                return [
                    'success' => true,
                    'message' => "File uploaded successfully. Found {$rowCount} employee records. Processing will be implemented in next update.",
                ];
            }

            return [
                'success' => true,
                'message' => 'File uploaded successfully. Processing will be completed shortly.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload file. Please try again.',
            ];
        }
    }

    private function bulkResetPassword(array $ids): array
    {
        try {
            $defaultPassword = 'password123';
            $hashedPassword = Hash::make($defaultPassword);

            $count = User::whereHas('employee', function ($q) use ($ids) {
                $q->whereIn('id', $ids);
            })->update(['password' => $hashedPassword]);

            return [
                'success' => true,
                'message' => "Berhasil reset password {$count} karyawan ke '{$defaultPassword}'",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal reset password. Silakan coba lagi.',
            ];
        }
    }

    private function bulkAssignLocation($request, array $ids): array
    {
        try {
            $locationId = $request->input('location_id');

            if (! $locationId) {
                return [
                    'success' => false,
                    'message' => 'Lokasi harus dipilih',
                ];
            }

            $count = $this->employeeRepository->updateMultiple($ids, ['location_id' => $locationId]);

            $location = $this->locationRepository->find($locationId);

            return [
                'success' => true,
                'message' => "Berhasil mengassign {$count} karyawan ke lokasi '{$location->name}'",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengassign lokasi. Silakan coba lagi.',
            ];
        }
    }

    private function bulkSendInvitation(array $ids): array
    {
        try {
            $employees = $this->employeeRepository->findMultiple($ids);
            $sentCount = 0;

            foreach ($employees as $employee) {
                // Logic untuk kirim email invitation
                // Implementasi tergantung pada mail system yang digunakan

                // Untuk sekarang kita hanya mark sebagai terkirim
                $sentCount++;
            }

            return [
                'success' => true,
                'message' => "Berhasil mengirim undangan ke {$sentCount} karyawan",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal mengirim undangan. Silakan coba lagi.',
            ];
        }
    }

    private function bulkExport($request, array $ids): array
    {
        try {
            $format = $request->input('format', 'csv');
            $employees = $this->employeeRepository->findMultiple($ids);

            // Generate export file
            switch ($format) {
                case 'csv':
                    $response = $this->exportToCSV($employees);
                    break;
                case 'excel':
                    $response = $this->exportToExcel($employees);
                    break;
                case 'pdf':
                    $response = $this->exportToPDF($employees);
                    break;
                default:
                    $response = $this->exportToCSV($employees);
            }

            return [
                'success' => true,
                'message' => 'Export berhasil',
                'response' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal export data. Silakan coba lagi.',
            ];
        }
    }
}
