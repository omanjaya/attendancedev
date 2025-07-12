<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'early_departure', 'incomplete'])->default('incomplete');
            
            // Face detection verification
            $table->decimal('check_in_confidence', 5, 4)->nullable(); // Face recognition confidence for check-in
            $table->decimal('check_out_confidence', 5, 4)->nullable(); // Face recognition confidence for check-out
            
            // Location verification
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->boolean('location_verified')->default(false);
            
            // Additional data
            $table->text('check_in_notes')->nullable();
            $table->text('check_out_notes')->nullable();
            $table->jsonb('metadata')->default('{}'); // Additional data like device info, IP, etc.
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['employee_id', 'date']);
            $table->index('date');
            $table->index('status');
            $table->index(['check_in_time', 'check_out_time']);
            $table->unique(['employee_id', 'date']); // One attendance record per employee per day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};