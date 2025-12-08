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
        // Drop views that depend on employees table to avoid SQLite issues
        DB::statement('DROP VIEW IF EXISTS effective_employee_schedules');
        DB::statement('DROP VIEW IF EXISTS teaching_schedule_overrides');

        Schema::table('employees', function (Blueprint $table) {
            // Add foreign key to employee_types
            $table->uuid('employee_type_id')->nullable();
            
            // SQLite doesn't support adding foreign key constraints to existing tables
            // We'll skip foreign key for SQLite
        });
        
        // Add foreign key constraint for non-SQLite databases
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreign('employee_type_id')
                    ->references('id')
                    ->on('employee_types')
                    ->onDelete('set null');
            });
        }

        // Recreate the views
        $this->recreateViews();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop views first
        DB::statement('DROP VIEW IF EXISTS effective_employee_schedules');
        DB::statement('DROP VIEW IF EXISTS teaching_schedule_overrides');

        Schema::table('employees', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['employee_type_id']);
            }
            $table->dropColumn('employee_type_id');
        });

        // Recreate the views
        $this->recreateViews();
    }

    private function recreateViews(): void
    {
        // Recreate effective_employee_schedules view
        DB::statement('DROP VIEW IF EXISTS effective_employee_schedules');
        
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("
                CREATE VIEW IF NOT EXISTS effective_employee_schedules AS
                SELECT
                    ems.id,
                    ems.employee_id,
                    ems.effective_date,
                    COALESCE(ts.teaching_start_time, ems.start_time) as effective_start_time,
                    COALESCE(ts.teaching_end_time, ems.end_time) as effective_end_time,
                    ems.location_id,
                    ems.status,
                    CASE
                        WHEN ts.id IS NOT NULL THEN 'teaching_override'
                        WHEN ems.is_holiday = 1 THEN 'holiday'
                        ELSE 'base_schedule'
                    END as schedule_type,
                    ts.id as teaching_schedule_id,
                    ts.subject_id,
                    ems.monthly_schedule_id
                FROM employee_monthly_schedules ems
                LEFT JOIN employees e ON e.id = ems.employee_id
                LEFT JOIN teaching_schedules ts ON
                    ts.teacher_id = ems.employee_id
                    AND ts.is_active = 1
                    AND ts.override_attendance = 1
                    AND LOWER(strftime('%A', ems.effective_date)) = ts.day_of_week
                    AND ems.effective_date >= ts.effective_from
                    AND (ts.effective_until IS NULL OR ems.effective_date <= ts.effective_until)
                WHERE ems.deleted_at IS NULL
            ");
        } else {
            // PostgreSQL version
            DB::statement("
                CREATE OR REPLACE VIEW effective_employee_schedules AS
                SELECT
                    ems.id,
                    ems.employee_id,
                    ems.effective_date,
                    COALESCE(ts.teaching_start_time, ems.start_time) as effective_start_time,
                    COALESCE(ts.teaching_end_time, ems.end_time) as effective_end_time,
                    ems.location_id,
                    ems.status,
                    CASE
                        WHEN ts.id IS NOT NULL THEN 'teaching_override'
                        WHEN ems.is_holiday = true THEN 'holiday'
                        ELSE 'base_schedule'
                    END as schedule_type,
                    ts.id as teaching_schedule_id,
                    ts.subject_id,
                    ems.monthly_schedule_id
                FROM employee_monthly_schedules ems
                LEFT JOIN employees e ON e.id = ems.employee_id
                LEFT JOIN teaching_schedules ts ON
                    ts.teacher_id = ems.employee_id
                    AND ts.is_active = true
                    AND ts.override_attendance = true
                    AND LOWER(TO_CHAR(ems.effective_date, 'day')) = ts.day_of_week
                    AND ems.effective_date >= ts.effective_from
                    AND (ts.effective_until IS NULL OR ems.effective_date <= ts.effective_until)
                WHERE ems.deleted_at IS NULL
            ");
        }
    }
};
