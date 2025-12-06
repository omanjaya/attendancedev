<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds metadata fields to track which face recognition model is being used
     * to support both face-api.js (128-d) and DeepFace ArcFace (512-d) embeddings
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Track which face recognition model/algorithm was used
            // Options: 'face-api.js', 'deepface-arcface', 'deepface-buffalo_l', etc.
            $table->string('face_model', 50)
                ->nullable()
                
                ->comment('Face recognition model used (face-api.js, deepface-arcface, etc.)');

            // Track embedding dimension (128 for face-api.js, 512 for ArcFace)
            $table->unsignedSmallInteger('face_embedding_dimension')
                ->nullable()
                
                ->comment('Dimension of face embedding (128, 512, etc.)');

            // Confidence score from face detection/extraction
            $table->decimal('face_confidence', 5, 4)
                ->nullable()
                
                ->comment('Confidence score from face detection (0.0000 to 1.0000)');

            // Image quality metrics
            $table->json('face_quality_metrics')
                ->nullable()
                
                ->comment('Quality metrics: blur_score, brightness, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'face_model',
                'face_embedding_dimension',
                'face_confidence',
                'face_quality_metrics'
            ]);
        });
    }
};
