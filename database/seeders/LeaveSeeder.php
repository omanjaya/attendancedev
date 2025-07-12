<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LeaveSeeder extends Seeder
{
    public function run()
    {
        $employees = Employee::where('is_active', true)->get();
        
        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Run EmployeeSeeder first.');
            return;
        }

        $this->command->info("Creating leave balances and leave requests for {$employees->count()} employees...");

        $leaveTypes = [
            'annual' => ['name' => 'Annual Leave', 'default_days' => 21],
            'sick' => ['name' => 'Sick Leave', 'default_days' => 10],
            'personal' => ['name' => 'Personal Leave', 'default_days' => 5],
            'maternity' => ['name' => 'Maternity Leave', 'default_days' => 90],
            'paternity' => ['name' => 'Paternity Leave', 'default_days' => 14],
            'emergency' => ['name' => 'Emergency Leave', 'default_days' => 3],
        ];

        // Create leave balances for all employees
        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type => $config) {
                // Adjust leave days based on employee type
                $maxDays = match($employee->employee_type) {
                    'permanent' => $config['default_days'],
                    'honorary' => round($config['default_days'] * 0.5), // Half for honorary
                    'staff' => round($config['default_days'] * 0.8), // 80% for staff
                    default => $config['default_days'],
                };

                $usedDays = rand(0, min(5, $maxDays)); // Some random usage

                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type' => $type,
                    'year' => date('Y'),
                    'allocated_days' => $maxDays,
                    'used_days' => $usedDays,
                    'remaining_days' => $maxDays - $usedDays,
                    'carry_forward_days' => rand(0, 3), // Some carry forward
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('Leave balances created successfully!');

        // Create some leave requests with various statuses
        $approvers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'superadmin', 'manager']);
        })->get();

        foreach ($employees as $employee) {
            // Each employee has 0-4 leave requests
            $requestCount = rand(0, 4);
            
            for ($i = 0; $i < $requestCount; $i++) {
                $leaveType = array_rand($leaveTypes);
                $startDate = Carbon::now()->addDays(rand(-60, 60)); // Past or future requests
                $duration = rand(1, match($leaveType) {
                    'annual' => 10,
                    'sick' => 3,
                    'personal' => 2,
                    'maternity', 'paternity' => 30,
                    'emergency' => 1,
                    default => 5,
                });
                
                $endDate = $startDate->copy()->addDays($duration - 1);
                
                // Determine status based on dates
                $status = $this->determineLeaveStatus($startDate, $endDate);
                
                $leave = Leave::create([
                    'id' => (string) Str::uuid(),
                    'employee_id' => $employee->id,
                    'leave_type' => $leaveType,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days_requested' => $duration,
                    'reason' => $this->getLeaveReason($leaveType),
                    'status' => $status,
                    'applied_at' => $startDate->copy()->subDays(rand(1, 14)), // Applied 1-14 days before
                    'attachments' => rand(0, 10) < 3 ? [ // 30% chance of attachments
                        'medical_certificate.pdf',
                        'travel_documents.pdf'
                    ] : null,
                    'employee_notes' => rand(0, 10) < 4 ? $this->getEmployeeNotes() : null,
                ]);

                // Add approval/rejection data for processed requests
                if (in_array($status, ['approved', 'rejected']) && $approvers->isNotEmpty()) {
                    $approver = $approvers->random();
                    $processedAt = $leave->applied_at->copy()->addDays(rand(1, 5));
                    
                    $leave->update([
                        'approved_by' => $approver->id,
                        'approved_at' => $processedAt,
                        'approval_notes' => $status === 'rejected' ? $this->getRejectionReason() : 
                                          (rand(0, 10) < 3 ? 'Approved as requested' : null),
                    ]);

                    // Update leave balance if approved
                    if ($status === 'approved') {
                        $balance = LeaveBalance::where('employee_id', $employee->id)
                                             ->where('leave_type', $leaveType)
                                             ->where('year', $startDate->year)
                                             ->first();
                        
                        if ($balance) {
                            $balance->update([
                                'used_days' => $balance->used_days + $duration,
                                'remaining_days' => $balance->remaining_days - $duration,
                            ]);
                        }
                    }
                }

                $this->command->line("  - Created {$leaveType} leave for {$employee->first_name} {$employee->last_name}: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} ({$status})");
            }
        }

        $this->command->info('Leave requests seeding completed successfully!');
        $this->command->info('Total leave requests: ' . Leave::count());
        
        // Show statistics
        $statusCounts = Leave::selectRaw('status, COUNT(*) as count')
                           ->groupBy('status')
                           ->pluck('count', 'status')
                           ->toArray();
        
        $this->command->table(
            ['Status', 'Count'],
            collect($statusCounts)->map(function($count, $status) {
                return [ucfirst($status), $count];
            })->toArray()
        );
    }

    private function determineLeaveStatus(Carbon $startDate, Carbon $endDate): string
    {
        $now = Carbon::now();
        
        // Past leaves are mostly approved (90%)
        if ($endDate->lt($now)) {
            return rand(0, 10) < 9 ? 'approved' : 'rejected';
        }
        
        // Future leaves have mixed status
        if ($startDate->gt($now->copy()->addDays(7))) {
            $rand = rand(0, 10);
            if ($rand < 6) return 'approved';
            if ($rand < 8) return 'pending';
            return 'rejected';
        }
        
        // Near-term leaves are mostly processed
        $rand = rand(0, 10);
        if ($rand < 7) return 'approved';
        if ($rand < 9) return 'pending';
        return 'rejected';
    }

    private function getLeaveReason(string $leaveType): string
    {
        $reasons = [
            'annual' => [
                'Family vacation',
                'Personal travel',
                'Rest and relaxation',
                'Wedding anniversary',
                'Family gathering',
            ],
            'sick' => [
                'Flu symptoms',
                'Medical appointment',
                'Recovery from illness',
                'Dental procedure',
                'Health check-up',
            ],
            'personal' => [
                'Personal matter',
                'Family emergency',
                'Home maintenance',
                'Legal appointment',
                'Personal development',
            ],
            'maternity' => [
                'Maternity leave for childbirth',
                'Prenatal care',
                'Post-delivery recovery',
            ],
            'paternity' => [
                'Paternity leave for new child',
                'Supporting spouse after delivery',
                'Bonding time with newborn',
            ],
            'emergency' => [
                'Family emergency',
                'Urgent personal matter',
                'Unexpected situation',
                'Critical family issue',
            ],
        ];

        $typeReasons = $reasons[$leaveType] ?? ['Personal reasons'];
        return $typeReasons[array_rand($typeReasons)];
    }

    private function getEmployeeNotes(): string
    {
        $notes = [
            'Will be available via phone if urgent',
            'All pending work has been handed over',
            'Emergency contact information provided',
            'Will check emails periodically',
            'Coverage arrangements made with colleagues',
            'All projects on track before departure',
        ];

        return $notes[array_rand($notes)];
    }

    private function getRejectionReason(): string
    {
        $reasons = [
            'Insufficient leave balance',
            'Critical project deadline conflicts',
            'Staffing constraints during requested period',
            'Previous leave request overlaps',
            'Business critical period',
            'Required documentation not provided',
        ];

        return $reasons[array_rand($reasons)];
    }
}