<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DeepFaceLoadBalancer;
use App\Services\FaceEmbeddingCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessFaceVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    /**
     * Data for face verification
     */
    protected string $imagePath;
    protected string $userId;
    protected string $verificationType; // 'check-in' or 'check-out'
    protected array $metadata;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $imagePath,
        string $userId,
        string $verificationType = 'check-in',
        array $metadata = []
    ) {
        $this->imagePath = $imagePath;
        $this->userId = $userId;
        $this->verificationType = $verificationType;
        $this->metadata = $metadata;
    }

    /**
     * Execute the job.
     */
    public function handle(
        DeepFaceLoadBalancer $loadBalancer,
        FaceEmbeddingCache $cache
    ): void {
        Log::info('Processing face verification job', [
            'user_id' => $this->userId,
            'type' => $this->verificationType,
            'attempt' => $this->attempts()
        ]);

        try {
            // Get user
            $user = User::find($this->userId);
            if (!$user) {
                throw new \Exception("User not found: {$this->userId}");
            }

            // Get image file
            if (!Storage::exists($this->imagePath)) {
                throw new \Exception("Image file not found: {$this->imagePath}");
            }

            $imageContent = Storage::get($this->imagePath);
            $tempFile = tmpfile();
            $tempPath = stream_get_meta_data($tempFile)['uri'];
            file_put_contents($tempPath, $imageContent);

            // Create UploadedFile
            $uploadedFile = new \Illuminate\Http\UploadedFile(
                $tempPath,
                'face.jpg',
                'image/jpeg',
                null,
                true
            );

            // Extract embedding using load balancer
            $result = $loadBalancer->extractEmbedding($uploadedFile);

            if (!$result['success']) {
                throw new \Exception("Failed to extract embedding: " . ($result['message'] ?? 'Unknown error'));
            }

            // Cache the embedding if it's a registration
            if ($user->employee && isset($result['embedding'])) {
                $cache->setEmbedding($user->employee->id, [
                    'employee_id' => $user->employee->id,
                    'employee_code' => $user->employee->employee_code,
                    'name' => $user->employee->full_name,
                    'embedding' => $result['embedding'],
                    'algorithm' => 'deepface-arcface',
                    'confidence' => $result['confidence'] ?? 0.0,
                ]);
            }

            // Clean up temp file
            fclose($tempFile);

            // Clean up stored image
            Storage::delete($this->imagePath);

            Log::info('Face verification job completed successfully', [
                'user_id' => $this->userId,
                'confidence' => $result['confidence'] ?? 0.0
            ]);

        } catch (\Exception $e) {
            Log::error('Face verification job failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Cleanup on failure
            if (Storage::exists($this->imagePath)) {
                Storage::delete($this->imagePath);
            }

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Face verification job failed permanently', [
            'user_id' => $this->userId,
            'type' => $this->verificationType,
            'error' => $exception->getMessage()
        ]);

        // Cleanup
        if (Storage::exists($this->imagePath)) {
            Storage::delete($this->imagePath);
        }

        // Optionally notify user or admin
        // event(new FaceVerificationFailed($this->userId, $exception->getMessage()));
    }
}
