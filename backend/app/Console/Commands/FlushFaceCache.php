<?php

namespace App\Console\Commands;

use App\Services\FaceEmbeddingCache;
use Illuminate\Console\Command;

class FlushFaceCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'face:cache-flush
                            {--employee= : Flush cache for specific employee ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush face embedding cache';

    /**
     * Execute the console command.
     */
    public function handle(FaceEmbeddingCache $cache): int
    {
        $employeeId = $this->option('employee');

        if ($employeeId) {
            // Flush specific employee
            $this->info("Flushing cache for employee: {$employeeId}");

            if ($cache->invalidate($employeeId)) {
                $this->info('✓ Cache flushed successfully');
            } else {
                $this->error('✗ Failed to flush cache');
                return Command::FAILURE;
            }

        } else {
            // Flush all
            if ($this->confirm('Flush ALL face embedding caches?')) {
                $this->warn('Flushing all caches...');

                if ($cache->flush()) {
                    $this->info('✓ All caches flushed successfully');
                    $this->info('Run "php artisan face:cache-warmup" to repopulate cache');
                } else {
                    $this->error('✗ Failed to flush caches');
                    return Command::FAILURE;
                }
            } else {
                $this->info('Cancelled');
            }
        }

        return Command::SUCCESS;
    }
}
