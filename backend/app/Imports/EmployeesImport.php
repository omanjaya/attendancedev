<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeType;
use App\Models\Department;
use App\Models\Position;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithValidation;
use Carbon\Carbon;

class EmployeesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected array $results = [
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    protected array $employeeTypes = [];
    protected array $departments = [];
    protected array $positions = [];
    protected array $locations = [];

    public function __construct()
    {
        // Pre-load reference data for performance
        $this->employeeTypes = EmployeeType::pluck('id', 'code')->toArray();
        $this->departments = Department::pluck('id', 'code')->toArray();
        $this->positions = Position::pluck('id', 'code')->toArray();
        $this->locations = Location::pluck('id', 'name')->toArray();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 for header row and 0-index
            
            try {
                $this->processRow($row->toArray(), $rowNumber);
            } catch (\Exception $e) {
                $this->results['errors'][] = [
                    'row' => $rowNumber,
                    'column' => '',
                    'value' => '',
                    'message' => $e->getMessage(),
                ];
                $this->results['skipped']++;
            }
        }
    }

    protected function processRow(array $row, int $rowNumber): void
    {
        // Normalize keys (convert to snake_case)
        $data = $this->normalizeRow($row);

        // Validate required fields
        $validation = $this->validateRow($data, $rowNumber);
        if (!$validation['valid']) {
            foreach ($validation['errors'] as $error) {
                $this->results['errors'][] = $error;
            }
            $this->results['skipped']++;
            return;
        }

        // Check if employee exists by email
        $existingUser = User::where('email', $data['email'])->first();
        
        if ($existingUser && $existingUser->employee) {
            // Update existing employee
            $this->updateEmployee($existingUser->employee, $data);
            $this->results['updated']++;
        } else {
            // Create new employee
            $this->createEmployee($data);
            $this->results['imported']++;
        }
    }

    protected function normalizeRow(array $row): array
    {
        $normalized = [];
        
        // Map common variations of column names
        $columnMap = [
            'full_name' => ['full_name', 'nama_lengkap', 'nama', 'name', 'fullname'],
            'email' => ['email', 'e-mail', 'email_address'],
            'password' => ['password', 'kata_sandi', 'pass', 'pwd'],
            'phone' => ['phone', 'telepon', 'no_telepon', 'phone_number', 'hp', 'no_hp'],
            'employee_id' => ['employee_id', 'nip', 'nik', 'id_pegawai', 'nomor_induk'],
            'employee_type' => ['employee_type', 'jenis_pegawai', 'tipe', 'type', 'jenis'],
            'department' => ['department', 'unit_kerja', 'departemen', 'unit'],
            'position' => ['position', 'jabatan', 'posisi'],
            'hire_date' => ['hire_date', 'tanggal_masuk', 'tgl_masuk', 'start_date', 'mulai_kerja'],
            'birth_date' => ['birth_date', 'tanggal_lahir', 'tgl_lahir', 'dob'],
            'gender' => ['gender', 'jenis_kelamin', 'kelamin'],
            'address' => ['address', 'alamat'],
            'location' => ['location', 'lokasi', 'tempat_kerja'],
            'salary_type' => ['salary_type', 'tipe_gaji', 'jenis_gaji'],
            'is_active' => ['is_active', 'aktif', 'status', 'active'],
        ];

        foreach ($columnMap as $normalizedKey => $variations) {
            foreach ($variations as $variation) {
                $snakeVariation = Str::snake($variation);
                foreach ($row as $key => $value) {
                    $snakeKey = Str::snake(trim($key));
                    if ($snakeKey === $snakeVariation || strtolower($snakeKey) === strtolower($variation)) {
                        $normalized[$normalizedKey] = $this->cleanValue($value);
                        break 2;
                    }
                }
            }
        }

        return $normalized;
    }

    protected function cleanValue($value)
    {
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }

    protected function validateRow(array $data, int $rowNumber): array
    {
        $errors = [];
        
        // Required: full_name
        if (empty($data['full_name'])) {
            $errors[] = [
                'row' => $rowNumber,
                'column' => 'full_name',
                'value' => $data['full_name'] ?? '',
                'message' => 'Nama lengkap wajib diisi',
            ];
        }

        // Required: email
        if (empty($data['email'])) {
            $errors[] = [
                'row' => $rowNumber,
                'column' => 'email',
                'value' => $data['email'] ?? '',
                'message' => 'Email wajib diisi',
            ];
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'row' => $rowNumber,
                'column' => 'email',
                'value' => $data['email'],
                'message' => 'Format email tidak valid',
            ];
        }

        // Required: employee_type
        if (empty($data['employee_type'])) {
            $errors[] = [
                'row' => $rowNumber,
                'column' => 'employee_type',
                'value' => $data['employee_type'] ?? '',
                'message' => 'Jenis pegawai wajib diisi',
            ];
        } elseif (!isset($this->employeeTypes[strtoupper($data['employee_type'])])) {
            // Try to find by name
            $type = EmployeeType::whereRaw('LOWER(name) = ?', [strtolower($data['employee_type'])])->first();
            if (!$type) {
                $errors[] = [
                    'row' => $rowNumber,
                    'column' => 'employee_type',
                    'value' => $data['employee_type'],
                    'message' => 'Jenis pegawai tidak ditemukan: ' . $data['employee_type'],
                ];
            }
        }

        // Required: hire_date
        if (empty($data['hire_date'])) {
            $errors[] = [
                'row' => $rowNumber,
                'column' => 'hire_date',
                'value' => $data['hire_date'] ?? '',
                'message' => 'Tanggal masuk wajib diisi',
            ];
        } else {
            try {
                Carbon::parse($data['hire_date']);
            } catch (\Exception $e) {
                $errors[] = [
                    'row' => $rowNumber,
                    'column' => 'hire_date',
                    'value' => $data['hire_date'],
                    'message' => 'Format tanggal masuk tidak valid',
                ];
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    protected function createEmployee(array $data): Employee
    {
        // Use password from Excel if provided, otherwise generate random
        $password = !empty($data['password']) ? $data['password'] : Str::random(10);

        // Create user
        $user = User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($password),
            'force_password_change' => true, // Always force password change on first login
        ]);

        // Assign role based on employee type
        $employeeTypeId = $this->resolveEmployeeType($data['employee_type']);
        $employeeType = EmployeeType::find($employeeTypeId);
        
        if ($employeeType) {
            $roleName = $this->getRoleFromEmployeeType($employeeType->code);
            $user->assignRole($roleName);
        } else {
            $user->assignRole('Pegawai');
        }

        // Create employee
        $employee = Employee::create([
            'user_id' => $user->id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'employee_id' => $data['employee_id'] ?? $this->generateEmployeeId(),
            'employee_type' => $employeeTypeId,
            'department_id' => $this->resolveDepartment($data['department'] ?? null),
            'position_id' => $this->resolvePosition($data['position'] ?? null),
            'location_id' => $this->resolveLocation($data['location'] ?? null),
            'hire_date' => Carbon::parse($data['hire_date'])->format('Y-m-d'),
            'birth_date' => !empty($data['birth_date']) ? Carbon::parse($data['birth_date'])->format('Y-m-d') : null,
            'gender' => $this->normalizeGender($data['gender'] ?? null),
            'address' => $data['address'] ?? null,
            'salary_type' => $data['salary_type'] ?? 'monthly',
            'is_active' => $this->normalizeBool($data['is_active'] ?? true),
        ]);

        Log::info('Employee imported', [
            'employee_id' => $employee->id,
            'email' => $employee->email,
        ]);

        return $employee;
    }

    protected function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? $employee->phone,
            'employee_type' => $this->resolveEmployeeType($data['employee_type']) ?? $employee->employee_type,
            'department_id' => $this->resolveDepartment($data['department'] ?? null) ?? $employee->department_id,
            'position_id' => $this->resolvePosition($data['position'] ?? null) ?? $employee->position_id,
            'location_id' => $this->resolveLocation($data['location'] ?? null) ?? $employee->location_id,
            'hire_date' => Carbon::parse($data['hire_date'])->format('Y-m-d'),
            'birth_date' => !empty($data['birth_date']) ? Carbon::parse($data['birth_date'])->format('Y-m-d') : $employee->birth_date,
            'gender' => $this->normalizeGender($data['gender'] ?? null) ?? $employee->gender,
            'address' => $data['address'] ?? $employee->address,
        ]);

        // Update user name if changed
        if ($employee->user && $employee->user->name !== $data['full_name']) {
            $employee->user->update(['name' => $data['full_name']]);
        }

        return $employee;
    }

    protected function resolveEmployeeType(?string $value): ?string
    {
        if (empty($value)) return null;
        
        $code = strtoupper(trim($value));
        
        if (isset($this->employeeTypes[$code])) {
            return $this->employeeTypes[$code];
        }

        // Try by name
        $type = EmployeeType::whereRaw('LOWER(name) = ?', [strtolower($value)])->first();
        return $type?->id;
    }

    protected function resolveDepartment(?string $value): ?string
    {
        if (empty($value)) return null;
        
        $code = strtoupper(trim($value));
        
        if (isset($this->departments[$code])) {
            return $this->departments[$code];
        }

        // Try by name
        $dept = Department::whereRaw('LOWER(name) = ?', [strtolower($value)])->first();
        return $dept?->id;
    }

    protected function resolvePosition(?string $value): ?string
    {
        if (empty($value)) return null;
        
        $code = strtoupper(trim($value));
        
        if (isset($this->positions[$code])) {
            return $this->positions[$code];
        }

        // Try by name
        $pos = Position::whereRaw('LOWER(name) = ?', [strtolower($value)])->first();
        return $pos?->id;
    }

    protected function resolveLocation(?string $value): ?string
    {
        if (empty($value)) return null;
        
        if (isset($this->locations[$value])) {
            return $this->locations[$value];
        }

        // Try case insensitive
        foreach ($this->locations as $name => $id) {
            if (strtolower($name) === strtolower($value)) {
                return $id;
            }
        }

        // Try by first match
        $location = Location::where('is_active', true)->first();
        return $location?->id;
    }

    protected function normalizeGender(?string $value): ?string
    {
        if (empty($value)) return null;
        
        $value = strtolower(trim($value));
        
        $maleVariants = ['male', 'laki-laki', 'laki', 'l', 'pria', 'm'];
        $femaleVariants = ['female', 'perempuan', 'wanita', 'p', 'w', 'f'];
        
        if (in_array($value, $maleVariants)) return 'male';
        if (in_array($value, $femaleVariants)) return 'female';
        
        return null;
    }

    protected function normalizeBool($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['1', 'true', 'yes', 'ya', 'aktif', 'active']);
        }
        return (bool) $value;
    }

    protected function getRoleFromEmployeeType(string $code): string
    {
        $roleMap = [
            'GURU' => 'Guru',
            'KEPSEK' => 'Kepala Sekolah',
            'ADMIN' => 'Admin',
            'TU' => 'Pegawai',
            'STAFF' => 'Pegawai',
        ];

        return $roleMap[strtoupper($code)] ?? 'Pegawai';
    }

    protected function generateEmployeeId(): string
    {
        $lastEmployee = Employee::orderBy('created_at', 'desc')->first();
        $lastNumber = 0;
        
        if ($lastEmployee && preg_match('/\d+$/', $lastEmployee->employee_id, $matches)) {
            $lastNumber = (int) $matches[0];
        }
        
        return 'EMP' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
