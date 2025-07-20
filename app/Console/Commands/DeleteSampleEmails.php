<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Employee;

class DeleteSampleEmails extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'employees:delete-samples {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Delete all sample emails from the database (@slub.ac.id domain)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Searching for sample emails with @slub.ac.id domain...');

        // Find all users with @slub.ac.id emails
        $sampleUsers = User::where('email', 'like', '%@slub.ac.id')->get();

        $this->info("Found {$sampleUsers->count()} sample users:");

        if ($sampleUsers->count() === 0) {
            $this->info('✅ No sample emails found to delete.');
            return 0;
        }

        // Display found users
        $this->table(
            ['Name', 'Email', 'Role'],
            $sampleUsers->map(function ($user) {
                return [
                    $user->name,
                    $user->email,
                    $user->getRoleNames()->implode(', ')
                ];
            })->toArray()
        );

        // Confirm deletion unless --force flag is used
        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  WARNING: This will permanently delete these users and their employee records. Continue?')) {
                $this->info('❌ Operation cancelled.');
                return 0;
            }
        }

        $this->info('🗑️  Deleting sample users and employees...');

        $deletedUsers = 0;
        $deletedEmployees = 0;

        $progressBar = $this->output->createProgressBar($sampleUsers->count());
        $progressBar->start();

        foreach ($sampleUsers as $user) {
            try {
                // Delete associated employee record first
                $employee = Employee::where('user_id', $user->id)->first();
                if ($employee) {
                    $employee->forceDelete(); // Hard delete
                    $deletedEmployees++;
                }
                
                // Delete user account
                $user->forceDelete(); // Hard delete
                $deletedUsers++;
                
                $progressBar->advance();
                
            } catch (\Exception $e) {
                $this->error("Error deleting {$user->email}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('🎉 Cleanup completed!');
        $this->table(
            ['Type', 'Count'],
            [
                ['Users deleted', $deletedUsers],
                ['Employee records deleted', $deletedEmployees],
            ]
        );

        return 0;
    }
}