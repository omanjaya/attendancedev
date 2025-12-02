<?php

namespace App\Console\Commands;

use App\Services\FaceEmbeddingCache;
use Illuminate\Console\Command;

class WarmUpFaceCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'face:cache-warmup
                            {--flush : Flush existing cache before warming up}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up face embedding cache with all employee data';

    /**
     * Execute the console command.
     */
    public function handle(FaceEmbeddingCache $cache): int
    {
        $this->info('Face Embedding Cache Warm-Up');
        $this->info('================================');
        $this->newLine();

        // Flush if requested
        if ($this->option('flush')) {
            $this->warn('Flushing existing cache...');
            $cache->flush();
            $this->info('✓ Cache flushed');
            $this->newLine();
        }

        // Warm up
        $this->info('Warming up cache...');

        $bar = $this->output->createProgressBar();
        $bar->start();

        $count = $cache->warmUp();

        $bar->finish();
        $this->newLine(2);

        // Show stats
        $this->info("✓ Cache warm-up completed!");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Employees Cached', $count],
                ['Cache Driver', config('cache.default')],
                ['Cache TTL', config('deepface.cache_ttl', 3600) . ' seconds'],
            ]
        );

        $this->newLine();
        $this->info('Cache is now ready for fast face verification!');

        return Command::SUCCESS;
    }
}
