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
        Schema::create('teaching_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Teacher assignment (must be employee with teacher role)
            $table->uuid('teacher_id');
            $table->foreign('teacher_id')->references('id')->on('employees')->onDelete('cascade');
            
            // Subject and class information
            $table->uuid('subject_id');
            $table->uuid('class_id')->nullable(); // Academic class (might not exist in current system)
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            
            // Schedule timing
            $table->enum('day_of_week', [
                'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
            ]);
            $table->time('teaching_start_time'); // e.g., 11:00
            $table->time('teaching_end_time'); // e.g., 13:00
            
            // Schedule validity period
            $table->date('effective_from'); // When this teaching schedule starts
            $table->date('effective_until')->nullable(); // When it ends (null = indefinite)
            
            // Teaching details
            $table->string('class_name')->nullable(); // e.g., "Kelas 5A", "Kelas 10 IPA"
            $table->string('room')->nullable(); // Classroom location
            $table->integer('student_count')->nullable(); // Number of students
            
            // Schedule status
            $table->boolean('is_active')->default(true);
            $table->enum('status', [
                'scheduled',    // Normal scheduled class
                'cancelled',    // Cancelled class
                'rescheduled',  // Moved to different time
                'substituted'   // Covered by substitute teacher
            ])->default('scheduled');
            
            // Override settings for attendance calculation
            $table->boolean('override_attendance')->default(true); // For Guru Honorer
            $table->boolean('strict_timing')->default(true); // Strict start time enforcement
            $table->integer('late_threshold_minutes')->default(15); // Minutes before considered late
            
            // Relationship to monthly schedules
            $table->uuid('monthly_schedule_id')->nullable(); // Link to base schedule
            $table->foreign('monthly_schedule_id')->references('id')->on('monthly_schedules')->onDelete('set null');
            
            // Additional teaching information
            $table->json('metadata')->nullable(); // {
            //     "curriculum": "K13",
            //     "semester": 1,
            //     "academic_year": "2024/2025",
            //     "lesson_plan_id": "uuid",
            //     "teaching_method": "online|offline|hybrid",
            //     "special_requirements": ["projector", "lab"],
            //     "assessment_schedule": {
            //         "quiz_dates": ["2025-01-15", "2025-02-15"],
            //         "exam_date": "2025-03-01"
            //     }
            // }
            
            // Substitute teacher management
            $table->uuid('substitute_teacher_id')->nullable();
            $table->foreign('substitute_teacher_id')->references('id')->on('employees')->onDelete('set null');
            $table->date('substitution_start_date')->nullable();
            $table->date('substitution_end_date')->nullable();
            $table->text('substitution_reason')->nullable();
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Performance indexes for common queries
            $table->index(['teacher_id', 'day_of_week', 'is_active']); // Teacher's weekly schedule
            $table->index(['effective_from', 'effective_until', 'is_active']); // Date range queries
            $table->index(['day_of_week', 'teaching_start_time']); // Time-based scheduling
            $table->index(['subject_id', 'class_name']); // Subject and class queries
            $table->index(['monthly_schedule_id', 'teacher_id']); // Schedule relationship
            $table->index(['status', 'is_active', 'effective_from']); // Status filtering
            
            // Complex indexes for attendance override queries
            $table->index(['teacher_id', 'day_of_week', 'effective_from', 'effective_until'], 'teacher_schedule_lookup');
            $table->index(['override_attendance', 'is_active', 'teacher_id'], 'attendance_override_lookup');
            
            // Foreign keys for audit
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Prevent scheduling conflicts for same teacher
            $table->unique([
                'teacher_id', 
                'day_of_week', 
                'teaching_start_time', 
                'effective_from'
            ], 'unique_teacher_time_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_schedules');
    }
};