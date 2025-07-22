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
        Schema::create('employee_monthly_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Reference to monthly schedule template
            $table->uuid('monthly_schedule_id');
            $table->foreign('monthly_schedule_id')->references('id')->on('monthly_schedules')->onDelete('cascade');
            
            // Employee assignment
            $table->uuid('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            
            // Specific date this schedule applies to
            $table->date('effective_date'); // Daily schedule entry
            
            // Working hours for this specific day (can override monthly default)
            $table->time('start_time');
            $table->time('end_time');
            
            // Location for this specific assignment
            $table->uuid('location_id');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            
            // Schedule status and override information
            $table->enum('status', [
                'active',       // Normal working day
                'overridden',   // Modified by admin or system
                'holiday',      // National/regional holiday
                'leave',        // Employee on leave
                'suspended'     // Temporarily disabled
            ])->default('active');
            
            // Override metadata for tracking changes
            $table->json('override_metadata')->nullable(); // {
            //     "override_type": "holiday|teaching|manual",
            //     "override_reason": "National Holiday: Independence Day",
            //     "original_start_time": "08:00:00",
            //     "original_end_time": "16:00:00",
            //     "override_by": "user_uuid",
            //     "override_at": "2025-01-15 10:30:00",
            //     "teaching_schedule_id": "uuid",
            //     "holiday_id": "uuid"
            // }
            
            // Working hours calculation cache
            $table->decimal('scheduled_hours', 4, 2)->default(8.00); // Expected work hours
            $table->boolean('is_weekend')->default(false);
            $table->boolean('is_holiday')->default(false);
            
            // Attendance tracking reference
            $table->uuid('attendance_id')->nullable(); // Link to actual attendance record
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('set null');
            
            // Audit fields
            $table->uuid('assigned_by')->nullable(); // Who assigned this schedule
            $table->uuid('modified_by')->nullable(); // Who last modified
            $table->timestamps();
            $table->softDeletes();
            
            // Performance indexes
            $table->index(['employee_id', 'effective_date']); // Primary lookup
            $table->index(['monthly_schedule_id', 'effective_date']); // Schedule-based queries
            $table->index(['effective_date', 'status']); // Date-based queries
            $table->index(['location_id', 'effective_date']); // Location-based queries
            $table->index(['status', 'is_holiday', 'is_weekend']); // Status filtering
            
            // Foreign keys for audit
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('modified_by')->references('id')->on('users')->onDelete('set null');
            
            // Unique constraint: One schedule per employee per day
            $table->unique(['employee_id', 'effective_date'], 'unique_employee_daily_schedule');
            
            // Compound indexes for common queries
            $table->index(['employee_id', 'effective_date', 'status'], 'employee_date_status');
            $table->index(['monthly_schedule_id', 'status', 'effective_date'], 'schedule_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_monthly_schedules');
    }
};