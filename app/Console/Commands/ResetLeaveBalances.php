<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetLeaveBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leave:reset-balances 
                            {--year= : The year to reset balances for (default: next year)}
                            {--carry-forward-limit=5 : Maximum days that can be carried forward}
                            {--dry-run : Run without making changes}
                            {--force : Force reset even if balances already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset leave balances for the new year, applying carry-forward rules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?? date('Y') + 1;
        $carryForwardLimit = $this->option('carry-forward-limit') ?? 5;
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Starting leave balance reset for year: {$year}");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all active employees
        $employees = Employee::where('is_active', true)->get();
        $this->info("Found {$employees->count()} active employees");

        // Get all active leave types
        $leaveTypes = LeaveType::active()->get();
        $this->info("Found {$leaveTypes->count()} active leave types");

        if ($employees->isEmpty() || $leaveTypes->isEmpty()) {
            $this->error('No active employees or leave types found. Aborting.');

            return Command::FAILURE;
        }

        // Check if balances already exist for the target year
        $existingBalances = LeaveBalance::where('year', $year)->count();
        if ($existingBalances > 0 && ! $force) {
            $this->error("Leave balances already exist for year {$year}. Use --force to overwrite.");

            return Command::FAILURE;
        }

        $this->info('Processing leave balance reset...');

        $created = 0;
        $updated = 0;
        $carried = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($employees->count() * $leaveTypes->count());
        $progressBar->start();

        DB::beginTransaction();

        try {
            foreach ($employees as $employee) {
                foreach ($leaveTypes as $leaveType) {
                    $progressBar->advance();

                    try {
                        // Get previous year balance for carry-forward calculation
                        $previousBalance = LeaveBalance::where('employee_id', $employee->id)
                            ->where('leave_type_id', $leaveType->id)
                            ->where('year', $year - 1)
                            ->first();

                        // Calculate carry-forward days
                        $carryForward = 0;
                        if ($previousBalance && $previousBalance->remaining_days > 0) {
                            $carryForward = min($previousBalance->remaining_days, $carryForwardLimit);
                            $carried += $carryForward;
                        }

                        // Check if balance already exists for this year
                        $existingBalance = LeaveBalance::where('employee_id', $employee->id)
                            ->where('leave_type_id', $leaveType->id)
                            ->where('year', $year)
                            ->first();

                        $allocatedDays = $leaveType->default_days_per_year ?? 20;

                        if ($existingBalance) {
                            if ($force && ! $dryRun) {
                                $existingBalance->update([
                                    'allocated_days' => $allocatedDays,
                                    'used_days' => 0,
                                    'carried_forward' => $carryForward,
                                    'metadata' => [
                                        'reset_at' => now()->toISOString(),
                                        'reset_by' => 'system_command',
                                        'carry_forward_from' => $year - 1,
                                        'carry_forward_limit' => $carryForwardLimit,
                                    ],
                                ]);
                                $existingBalance->updateRemainingDays();
                                $updated++;
                            }
                        } else {
                            if (! $dryRun) {
                                LeaveBalance::create([
                                    'employee_id' => $employee->id,
                                    'leave_type_id' => $leaveType->id,
                                    'year' => $year,
                                    'allocated_days' => $allocatedDays,
                                    'used_days' => 0,
                                    'remaining_days' => $allocatedDays + $carryForward,
                                    'carried_forward' => $carryForward,
                                    'metadata' => [
                                        'reset_at' => now()->toISOString(),
                                        'reset_by' => 'system_command',
                                        'carry_forward_from' => $year - 1,
                                        'carry_forward_limit' => $carryForwardLimit,
                                    ],
                                ]);
                            }
                            $created++;
                        }
                    } catch (\Exception $e) {
                        $errors[] =
                          "Error processing {$employee->full_name} - {$leaveType->name}: ".$e->getMessage();
                    }
                }
            }

            $progressBar->finish();
            $this->newLine();

            if ($dryRun) {
                $this->info('DRY RUN RESULTS:');
                $this->info("- Would create: {$created} new balances");
                $this->info("- Would update: {$updated} existing balances");
                $this->info("- Total carry-forward days: {$carried}");

                DB::rollBack();
            } else {
                DB::commit();

                $this->info('Leave balance reset completed successfully!');
                $this->info("- Created: {$created} new balances");
                $this->info("- Updated: {$updated} existing balances");
                $this->info("- Total carry-forward days: {$carried}");
            }

            if (! empty($errors)) {
                $this->error('Errors encountered:');
                foreach ($errors as $error) {
                    $this->error("- {$error}");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to reset leave balances: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
