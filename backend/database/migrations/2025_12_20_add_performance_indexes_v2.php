<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ========================================
        // Teaching Schedules Performance Indexes
        // ========================================

        // Composite index for most common teaching schedule queries
        // Used for finding teacher schedules by day and time
        Schema::table('teaching_schedules', function (Blueprint $table) {
            $table->index(
                ['teacher_id', 'day_of_week', 'teaching_start_time'],
                'idx_teaching_schedules_teacher_day_time'
            );
        });

        // Index for finding active schedules within date ranges
        // Used for schedule validation and filtering
        Schema::table('teaching_schedules', function (Blueprint $table) {
            $table->index(
                ['is_active', 'effective_from', 'effective_until'],
                'idx_teaching_schedules_active_dates'
            );
        });

        // ========================================
        // Employees Face Recognition GIN Index (PostgreSQL)
        // ========================================

        // GIN index for fast JSON queries on face recognition metadata
        // Used for finding employees with registered faces
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_employees_face_registered
            ON employees USING GIN ((metadata->\'face_recognition\'))'
        );

        // ========================================
        // Attendances Partial Index for Incomplete Checkouts
        // ========================================

        // Partial index for finding incomplete attendance records (checked in but not out)
        // This is frequently queried for end-of-day reports and auto-checkout processes
        DB::statement(
            'CREATE INDEX IF NOT EXISTS idx_attendances_incomplete
            ON attendances(date, employee_id)
            WHERE check_in_time IS NOT NULL AND check_out_time IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop teaching_schedules indexes
        Schema::table('teaching_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_teaching_schedules_teacher_day_time');
            $table->dropIndex('idx_teaching_schedules_active_dates');
        });

        // Drop PostgreSQL-specific indexes
        DB::statement('DROP INDEX IF EXISTS idx_employees_face_registered');
        DB::statement('DROP INDEX IF EXISTS idx_attendances_incomplete');
    }
};
