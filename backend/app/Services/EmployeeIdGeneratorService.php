<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * Employee ID Generator Service
 * 
 * Generates unique employee IDs based on role and employee type combination
 */
class EmployeeIdGeneratorService
{
    /**
     * Generate employee ID based on role and employee type
     * 
     * @param string $role User role (e.g., 'guru', 'admin', 'kepala_sekolah')
     * @param string $employeeType Employee type (e.g., 'permanent', 'honorary', 'staff')
     * @return string Generated employee ID
     */
    public function generateEmployeeId(string $role, string $employeeType): string
    {
        $prefix = $this->getPrefix($role, $employeeType);
        $nextNumber = $this->getNextNumber($prefix);
        
        return sprintf('%s%03d', $prefix, $nextNumber);
    }
    
    /**
     * Get prefix based on role and employee type combination
     */
    private function getPrefix(string $role, string $employeeType): string
    {
        // Normalize role name - handle both space and underscore formats
        $normalizedRole = strtolower(str_replace(' ', '_', $role));
        
        // Super Admin & Admin - role based only
        if ($normalizedRole === 'super_admin') {
            return 'SADM';
        }
        
        if ($normalizedRole === 'admin') {
            return 'ADMN';
        }
        
        if ($normalizedRole === 'kepala_sekolah') {
            return 'KPS';
        }
        
        // For guru and pegawai, combine with employee type
        if ($normalizedRole === 'guru') {
            if ($employeeType === 'permanent') {
                return 'GTTP'; // Guru Tetap
            } elseif ($employeeType === 'honorary') {
                return 'GHNR'; // Guru Honor
            }
            // Default for guru
            return 'GURU';
        }
        
        if ($normalizedRole === 'pegawai') {
            if ($employeeType === 'honorary') {
                return 'PHNR'; // Pegawai Honor
            }
            return 'PGWI'; // Pegawai default
        }
        
        // Default fallback
        return 'EMP';
    }
    
    /**
     * Get next available number for the given prefix
     */
    private function getNextNumber(string $prefix): int
    {
        // Use database locking to prevent race conditions
        return DB::transaction(function () use ($prefix) {
            $latestEmployee = Employee::where('employee_id', 'LIKE', $prefix . '%')
                ->orderBy('employee_id', 'desc')
                ->lockForUpdate()
                ->first();
            
            if (!$latestEmployee) {
                return 1;
            }
            
            // Extract number from the latest ID
            $latestId = $latestEmployee->employee_id;
            $number = (int) substr($latestId, strlen($prefix));
            
            return $number + 1;
        });
    }
    
    /**
     * Check if employee ID already exists
     */
    public function isEmployeeIdExists(string $employeeId): bool
    {
        return Employee::where('employee_id', $employeeId)->exists();
    }
    
    /**
     * Generate unique employee ID with retry mechanism
     */
    public function generateUniqueEmployeeId(string $role, string $employeeType): string
    {
        $maxAttempts = 10;
        $attempts = 0;
        
        do {
            $employeeId = $this->generateEmployeeId($role, $employeeType);
            $attempts++;
            
            if (!$this->isEmployeeIdExists($employeeId)) {
                return $employeeId;
            }
            
            // Add small delay to avoid rapid retries
            usleep(100000); // 100ms
            
        } while ($attempts < $maxAttempts);
        
        // If we still can't generate unique ID, throw exception
        throw new \RuntimeException("Unable to generate unique employee ID after {$maxAttempts} attempts");
    }
    
    /**
     * Get employee ID format information
     */
    public static function getIdFormats(): array
    {
        return [
            'super_admin' => [
                'prefix' => 'SADM',
                'description' => 'Super Admin',
                'example' => 'SADM001'
            ],
            'admin' => [
                'prefix' => 'ADMN', 
                'description' => 'Admin',
                'example' => 'ADMN001'
            ],
            'kepala_sekolah' => [
                'prefix' => 'KPS',
                'description' => 'Kepala Sekolah',
                'example' => 'KPS001'
            ],
            'guru_tetap' => [
                'prefix' => 'GTTP',
                'description' => 'Guru Tetap',
                'example' => 'GTTP001'
            ],
            'guru_honor' => [
                'prefix' => 'GHNR',
                'description' => 'Guru Honor', 
                'example' => 'GHNR001'
            ],
            'pegawai' => [
                'prefix' => 'PGWI',
                'description' => 'Pegawai',
                'example' => 'PGWI001'
            ],
            'pegawai_honor' => [
                'prefix' => 'PHNR',
                'description' => 'Pegawai Honor',
                'example' => 'PHNR001'
            ]
        ];
    }
}