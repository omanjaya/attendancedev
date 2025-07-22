<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Basic schedule information
            $table->string('name'); // Nama Jadwal
            $table->integer('month'); // 1-12 for January-December
            $table->integer('year'); // Target year
            
            // Date range for schedule validity
            $table->date('start_date'); // When this schedule starts
            $table->date('end_date'); // When this schedule ends
            
            // Default working hours for this schedule
            $table->time('default_start_time'); // e.g., 08:00
            $table->time('default_end_time'); // e.g., 16:00
            
            // Location reference
            $table->uuid('location_id');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            
            // Schedule configuration
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            
            // Additional schedule settings and rules
            $table->json('metadata')->nullable(); // {
            //     "work_days": ["monday", "tuesday", "wednesday", "thursday", "friday"],
            //     "break_time_start": "12:00",
            //     "break_time_end": "13:00",
            //     "overtime_allowed": true,
            //     "late_threshold_minutes": 15,
            //     "early_departure_threshold_minutes": 30
            // }
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['month', 'year', 'is_active']);
            $table->index(['location_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
            
            // Foreign key for audit
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Unique constraint to prevent duplicate schedules
            $table->unique(['name', 'month', 'year'], 'unique_monthly_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_schedules');
    }
};