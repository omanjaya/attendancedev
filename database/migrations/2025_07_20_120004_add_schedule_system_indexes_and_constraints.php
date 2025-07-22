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
        // Add computed columns and additional indexes for better performance
        
        // Add computed month/year columns to employee_monthly_schedules for faster filtering
        Schema::table('employee_monthly_schedules', function (Blueprint $table) {
            // Add computed columns for faster date-based queries (SQLite compatible)
            if (!Schema::hasColumn('employee_monthly_schedules', 'schedule_month')) {
                $table->integer('schedule_month')->storedAs("CAST(strftime('%m', effective_date) AS INTEGER)");
            }
            if (!Schema::hasColumn('employee_monthly_schedules', 'schedule_year')) {
                $table->integer('schedule_year')->storedAs("CAST(strftime('%Y', effective_date) AS INTEGER)");
            }
            if (!Schema::hasColumn('employee_monthly_schedules', 'day_name')) {
                $table->string('day_name')->storedAs("strftime('%w', effective_date)"); // 0=Sunday, 6=Saturday
            }
        });
        
        // Add indexes separately to avoid issues if columns already exist
        try {
            Schema::table('employee_monthly_schedules', function (Blueprint $table) {
                // Add indexes on computed columns
                $table->index(['schedule_year', 'schedule_month', 'status']);
                $table->index(['day_name', 'status', 'is_weekend']);
            });
        } catch (\Exception $e) {
            // Indexes might already exist, continue
        }
        
        // Add computed columns to teaching_schedules
        Schema::table('teaching_schedules', function (Blueprint $table) {
            // Teaching duration in minutes for quick calculations (SQLite compatible)
            if (!Schema::hasColumn('teaching_schedules', 'teaching_duration_minutes')) {
                $table->integer('teaching_duration_minutes')
                    ->storedAs("(strftime('%s', teaching_end_time) - strftime('%s', teaching_start_time)) / 60");
            }
        });
        
        try {
            Schema::table('teaching_schedules', function (Blueprint $table) {
                // Add index on duration for workload calculations
                $table->index(['teacher_id', 'teaching_duration_minutes', 'is_active']);
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
        
        // Add additional indexes to existing attendance table for schedule integration
        Schema::table('attendances', function (Blueprint $table) {
            // Add index for schedule override queries
            if (!Schema::hasColumn('attendances', 'schedule_source')) {
                $table->enum('schedule_source', [
                    'base_schedule',
                    'teaching_schedule', 
                    'manual_override',
                    'holiday_override'
                ])->default('base_schedule')->after('metadata');
            }
        });
        
        try {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index(['employee_id', 'date', 'schedule_source'], 'attendance_schedule_source');
            });
        } catch (\Exception $e) {
            // Index might already exist, continue
        }
        
        // Create view for quick schedule lookups
        try {
            DB::statement('
                CREATE VIEW IF NOT EXISTS effective_employee_schedules AS
            SELECT 
                ems.id,
                ems.employee_id,
                ems.effective_date,
                ems.start_time,
                ems.end_time,
                ems.status,
                ems.location_id,
                ms.name as schedule_name,
                ms.month,
                ms.year,
                e.first_name,
                e.last_name,
                e.employee_type,
                l.name as location_name,
                CASE 
                    WHEN nh.id IS NOT NULL THEN "holiday"
                    WHEN ems.status = "active" THEN "working"
                    ELSE ems.status
                END as computed_status
            FROM employee_monthly_schedules ems
            JOIN monthly_schedules ms ON ems.monthly_schedule_id = ms.id
            JOIN employees e ON ems.employee_id = e.id
            JOIN locations l ON ems.location_id = l.id
            LEFT JOIN national_holidays nh ON nh.holiday_date = ems.effective_date 
                AND (nh.location_id = ems.location_id OR nh.location_id IS NULL)
                AND nh.is_active = 1
            WHERE ems.deleted_at IS NULL 
                AND ms.deleted_at IS NULL 
                AND e.deleted_at IS NULL
            ');
        } catch (\Exception $e) {
            // View might already exist, continue
        }
        
        // Create view for teaching schedule overrides
        try {
            DB::statement('
                CREATE VIEW IF NOT EXISTS teaching_schedule_overrides AS
            SELECT 
                ts.id,
                ts.teacher_id,
                ts.day_of_week,
                ts.teaching_start_time,
                ts.teaching_end_time,
                ts.teaching_duration_minutes,
                ts.subject_id,
                ts.class_name,
                ts.room,
                ts.effective_from,
                ts.effective_until,
                ts.override_attendance,
                s.name as subject_name,
                e.first_name,
                e.last_name,
                e.employee_type,
                CASE 
                    WHEN e.employee_type = "guru_honorer" AND ts.override_attendance = 1 
                    THEN "override_applicable"
                    ELSE "override_not_applicable"
                END as override_status
            FROM teaching_schedules ts
            JOIN employees e ON ts.teacher_id = e.id
            JOIN subjects s ON ts.subject_id = s.id
            WHERE ts.deleted_at IS NULL 
                AND ts.is_active = 1
                AND e.deleted_at IS NULL
            ');
        } catch (\Exception $e) {
            // View might already exist, continue
        }
        
        // Create indexes on views (MySQL specific)
        // Note: These would be different for PostgreSQL
        
        // Note: SQLite doesn't support stored procedures, so schedule logic is handled in PHP
        // The GetEmployeeScheduleForDate functionality is implemented in ScheduleManagementService
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: No stored procedures to drop in SQLite
        
        // Drop views
        DB::statement('DROP VIEW IF EXISTS teaching_schedule_overrides');
        DB::statement('DROP VIEW IF EXISTS effective_employee_schedules');
        
        // Remove computed columns and indexes
        Schema::table('employee_monthly_schedules', function (Blueprint $table) {
            $table->dropIndex(['schedule_year', 'schedule_month', 'status']);
            $table->dropIndex(['day_name', 'status', 'is_weekend']);
            $table->dropColumn(['schedule_month', 'schedule_year', 'day_name']);
        });
        
        Schema::table('teaching_schedules', function (Blueprint $table) {
            $table->dropIndex(['teacher_id', 'teaching_duration_minutes', 'is_active']);
            $table->dropColumn('teaching_duration_minutes');
        });
        
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['employee_id', 'date', 'schedule_source']);
            if (Schema::hasColumn('attendances', 'schedule_source')) {
                $table->dropColumn('schedule_source');
            }
        });
    }
};