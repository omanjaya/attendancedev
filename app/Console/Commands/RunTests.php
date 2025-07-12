<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class RunTests extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:run 
                            {--suite= : Test suite to run (unit, feature, all)}
                            {--coverage : Generate coverage report}
                            {--filter= : Filter tests by name}
                            {--group= : Run tests from specific group}
                            {--stop-on-failure : Stop on first failure}
                            {--parallel : Run tests in parallel}';

    /**
     * The console command description.
     */
    protected $description = 'Run application tests with various options';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running attendance system tests...');

        $command = $this->buildTestCommand();
        
        $this->line("Executing: {$command}");
        $this->newLine();

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(600); // 10 minutes timeout

        $exitCode = $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if ($exitCode === 0) {
            $this->newLine();
            $this->info('✓ All tests passed!');
            
            if ($this->option('coverage')) {
                $this->info('Coverage report generated in build/coverage/');
            }
        } else {
            $this->newLine();
            $this->error('✗ Tests failed!');
        }

        return $exitCode;
    }

    /**
     * Build the PHPUnit test command.
     */
    private function buildTestCommand(): string
    {
        $command = ['vendor/bin/phpunit'];

        // Test suite selection
        if ($suite = $this->option('suite')) {
            if ($suite !== 'all') {
                $command[] = '--testsuite=' . ucfirst($suite);
            }
        }

        // Coverage report
        if ($this->option('coverage')) {
            $command[] = '--coverage-html=build/coverage';
            $command[] = '--coverage-text';
            $command[] = '--coverage-clover=build/logs/clover.xml';
        }

        // Filter tests
        if ($filter = $this->option('filter')) {
            $command[] = '--filter=' . escapeshellarg($filter);
        }

        // Test groups
        if ($group = $this->option('group')) {
            $command[] = '--group=' . escapeshellarg($group);
        }

        // Stop on failure
        if ($this->option('stop-on-failure')) {
            $command[] = '--stop-on-failure';
        }

        // Parallel execution
        if ($this->option('parallel')) {
            $command[] = '--parallel';
        }

        // Additional flags
        $command[] = '--colors=always';
        $command[] = '--verbose';

        return implode(' ', $command);
    }

    /**
     * Show test statistics and recommendations.
     */
    private function showTestStatistics(): void
    {
        $this->info('Test Statistics:');
        
        // Count test files
        $unitTests = glob(base_path('tests/Unit/*Test.php'));
        $featureTests = glob(base_path('tests/Feature/*Test.php'));
        
        $this->line("Unit Tests: " . count($unitTests));
        $this->line("Feature Tests: " . count($featureTests));
        $this->line("Total Test Files: " . (count($unitTests) + count($featureTests)));

        // Test coverage recommendations
        $this->newLine();
        $this->info('Coverage Recommendations:');
        $this->line('• Aim for >80% code coverage');
        $this->line('• Focus on business logic and critical paths');
        $this->line('• Test edge cases and error conditions');
        $this->line('• Mock external services in unit tests');
    }
}