<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Services\FaceEmbeddingCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CacheFaceEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 2;

    /**
     * Employee ID
     */
    protected string $employeeId;

    /**
     * Embedding data
     */
    protected array $embeddingData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $employeeId, array $embeddingData)
    {
        $this->employeeId = $employeeId;
        $this->embeddingData = $embeddingData;
    }

    /**
     * Execute the job.
     */
    public function handle(FaceEmbeddingCache $cache): void
    {
        Log::debug('Caching face embedding', [
            'employee_id' => $this->employeeId
        ]);

        try {
            $cache->setEmbedding($this->employeeId, $this->embeddingData);

            Log::info('Face embedding cached successfully', [
                'employee_id' => $this->employeeId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cache face embedding', [
                'employee_id' => $this->employeeId,
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw for retry
        }
    }
}
