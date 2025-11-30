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
        // Drop views temporarily to allow table alterations in SQLite
        DB::statement('DROP VIEW IF EXISTS effective_employee_schedules');
        DB::statement('DROP VIEW IF EXISTS teaching_schedule_overrides');
        
        // Add schedule-related fields to existing tables for integration
        
        // Add fields to employees table for schedule preferences
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                // Employee type classification for schedule rules
                // Note: employee_type column should already exist from create_employees_table migration
                // If it has different enum values, we'll work with the existing ones
                
                // Default schedule preferences
                if (!Schema::hasColumn('employees', 'default_location_id')) {
                    $table->uuid('default_location_id')->nullable();
                    $table->foreign('default_location_id')->references('id')->on('locations')->onDelete('set null');
                }
                
                // Schedule-specific settings
                if (!Schema::hasColumn('employees', 'schedule_preferences')) {
                    $table->json('schedule_preferences')->nullable(); // {
                    //     "preferred_start_time": "08:00",
                    //     "preferred_end_time": "16:00",
                    //     "flexible_hours": true,
                    //     "overtime_allowed": true,
                    //     "weekend_work": false,
                    //     "teaching_load_hours": 24, // For teachers
                    //     "max_consecutive_teaching_hours": 4
                    // }
                }
                
                // Teaching qualification for schedule assignment
                if (!Schema::hasColumn('employees', 'can_teach')) {
                    $table->boolean('can_teach')->default(false);
                }
                if (!Schema::hasColumn('employees', 'can_substitute')) {
                    $table->boolean('can_substitute')->default(false);
                }
            });
        }
        
        // Add schedule reference to attendances table if not exists
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                // Link to the schedule that this attendance is based on
                if (!Schema::hasColumn('attendances', 'employee_monthly_schedule_id')) {
                    $table->uuid('employee_monthly_schedule_id')->nullable();
                    $table->foreign('employee_monthly_schedule_id', 'att_emp_monthly_schedule_fk')
                          ->references('id')->on('employee_monthly_schedules')->onDelete('set null');
                }
                
                // Teaching schedule reference for overrides
                if (!Schema::hasColumn('attendances', 'teaching_schedule_id')) {
                    $table->uuid('teaching_schedule_id')->nullable();
                    $table->foreign('teaching_schedule_id', 'att_teaching_schedule_fk')
                          ->references('id')->on('teaching_schedules')->onDelete('set null');
                }
                
                // Holiday reference
                if (!Schema::hasColumn('attendances', 'holiday_id')) {
                    $table->uuid('holiday_id')->nullable();
                    $table->foreign('holiday_id', 'att_holiday_fk')
                          ->references('id')->on('national_holidays')->onDelete('set null');
                }
                
                // Schedule calculation metadata
                if (!Schema::hasColumn('attendances', 'schedule_metadata')) {
                    $table->json('schedule_metadata')->nullable(); // {
                    //     "schedule_type": "base|teaching|holiday",
                    //     "expected_start": "08:00:00",
                    //     "expected_end": "16:00:00",
                    //     "override_applied": true,
                    //     "override_reason": "Teaching schedule for Guru Honorer",
                    //     "calculated_at": "2025-01-15 07:30:00"
                    // }
                }
            });
        }
        
        // Extend leave requests to respect schedule system
        if (Schema::hasTable('leaves')) {
            Schema::table('leaves', function (Blueprint $table) {
                // Link leaves to monthly schedules for impact calculation
                if (!Schema::hasColumn('leaves', 'affected_schedules')) {
                    $table->json('affected_schedules')->nullable(); // [
                    //     {
                    //         "schedule_id": "uuid",
                    //         "date": "2025-01-15",
                    //         "original_hours": 8.0,
                    //         "leave_hours": 8.0
                    //     }
                    // ]
                }
                
                // Schedule override flag
                if (!Schema::hasColumn('leaves', 'override_schedules')) {
                    $table->boolean('override_schedules')->default(true);
                }
            });
        }
        
        // Add schedule tracking to existing employee_schedules table
        if (Schema::hasTable('employee_schedules')) {
            Schema::table('employee_schedules', function (Blueprint $table) {
                // Link to new monthly schedule system
                if (!Schema::hasColumn('employee_schedules', 'monthly_schedule_id')) {
                    $table->uuid('monthly_schedule_id')->nullable();
                    $table->foreign('monthly_schedule_id', 'emp_sch_monthly_fk')
                          ->references('id')->on('monthly_schedules')->onDelete('set null');
                }
                
                // Migration status
                if (!Schema::hasColumn('employee_schedules', 'migrated_to_monthly')) {
                    $table->boolean('migrated_to_monthly')->default(false);
                }
                if (!Schema::hasColumn('employee_schedules', 'migration_date')) {
                    $table->timestamp('migration_date')->nullable();
                }
            });
        }
        
        // Add indexes for performance
        if (Schema::hasTable('employees')) {
            try {
                Schema::table('employees', function (Blueprint $table) {
                    // Only create indexes if columns exist
                    if (Schema::hasColumn('employees', 'employee_type') && 
                        Schema::hasColumn('employees', 'can_teach') && 
                        Schema::hasColumn('employees', 'can_substitute')) {
                        $table->index(['employee_type', 'can_teach', 'can_substitute'], 'idx_employees_type_teach');
                    }
                    if (Schema::hasColumn('employees', 'default_location_id') && 
                        Schema::hasColumn('employees', 'employee_type')) {
                        $table->index(['default_location_id', 'employee_type'], 'idx_employees_location_type');
                    }
                });
            } catch (\Exception $e) {
                // Indexes might already exist, continue
            }
        }
        
        if (Schema::hasTable('attendances')) {
            try {
                Schema::table('attendances', function (Blueprint $table) {
                    if (Schema::hasColumn('attendances', 'employee_monthly_schedule_id')) {
                        $table->index(['employee_monthly_schedule_id', 'date']);
                    }
                    if (Schema::hasColumn('attendances', 'teaching_schedule_id')) {
                        $table->index(['teaching_schedule_id', 'date']);
                    }
                    if (Schema::hasColumn('attendances', 'holiday_id')) {
                        $table->index(['holiday_id', 'date']);
                    }
                    // Only create index if all columns exist
                    if (Schema::hasColumn('attendances', 'date') && Schema::hasColumn('attendances', 'status')) {
                        $table->index(['date', 'status']);
                    }
                });
            } catch (\Exception $e) {
                // Indexes might already exist, continue
            }
        }
        
        if (Schema::hasTable('leaves')) {
            try {
                Schema::table('leaves', function (Blueprint $table) {
                    $table->index(['override_schedules', 'start_date', 'end_date']);
                });
            } catch (\Exception $e) {
                // Indexes might already exist, continue
            }
        }
        
        // Recreate views after table alterations
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
            // View creation failed, continue
        }
        
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
            // View creation failed, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes first
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex(['override_schedules', 'start_date', 'end_date']);
        });
        
        try {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex(['employee_monthly_schedule_id', 'date']);
                $table->dropIndex(['teaching_schedule_id', 'date']);
                $table->dropIndex(['holiday_id', 'date']);
                $table->dropIndex(['date', 'status']);
            });
        } catch (\Exception $e) {
            // Indexes might not exist, continue
        }
        
        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex('idx_employees_type_teach');
                $table->dropIndex('idx_employees_location_type');
            });
        } catch (\Exception $e) {
            // Indexes might not exist, continue
        }
        
        // Remove foreign keys and columns
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['default_location_id']);
            $table->dropColumn([
                'employee_type', 
                'default_location_id', 
                'schedule_preferences', 
                'can_teach', 
                'can_substitute'
            ]);
        });
        
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign('att_emp_monthly_schedule_fk');
            $table->dropForeign('att_teaching_schedule_fk');
            $table->dropForeign('att_holiday_fk');
            $table->dropColumn([
                'employee_monthly_schedule_id',
                'teaching_schedule_id', 
                'holiday_id',
                'schedule_metadata'
            ]);
        });
        
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['affected_schedules', 'override_schedules']);
        });
        
        if (Schema::hasTable('employee_schedules')) {
            Schema::table('employee_schedules', function (Blueprint $table) {
                $table->dropForeign('emp_sch_monthly_fk');
                $table->dropColumn(['monthly_schedule_id', 'migrated_to_monthly', 'migration_date']);
            });
        }
    }
};