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
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Email already has unique index, but add index for lookups
            if (!$this->indexExists('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }

            // Index for active users
            if (!$this->indexExists('users', 'users_active_index')) {
                $table->index('is_active', 'users_active_index');
            }

            // Composite index for authentication queries
            if (!$this->indexExists('users', 'users_email_active_index')) {
                $table->index(['email', 'is_active'], 'users_email_active_index');
            }
        });

        // Employees table indexes
        Schema::table('employees', function (Blueprint $table) {
            // Employee ID for lookups
            if (!$this->indexExists('employees', 'employees_employee_id_index')) {
                $table->index('employee_id', 'employees_employee_id_index');
            }

            // User ID for joins
            if (!$this->indexExists('employees', 'employees_user_id_index')) {
                $table->index('user_id', 'employees_user_id_index');
            }

            // Active status filter
            if (!$this->indexExists('employees', 'employees_active_index')) {
                $table->index('is_active', 'employees_active_index');
            }

            // Composite for common queries
            if (!$this->indexExists('employees', 'employees_user_active_index')) {
                $table->index(['user_id', 'is_active'], 'employees_user_active_index');
            }
        });

        // Attendances table indexes (CRITICAL for performance)
        Schema::table('attendances', function (Blueprint $table) {
            // Employee lookups (most common query)
            if (!$this->indexExists('attendances', 'attendances_employee_id_index')) {
                $table->index('employee_id', 'attendances_employee_id_index');
            }

            // Date range queries
            if (!$this->indexExists('attendances', 'attendances_date_index')) {
                $table->index('date', 'attendances_date_index');
            }

            // Check-in time queries
            if (!$this->indexExists('attendances', 'attendances_check_in_time_index')) {
                $table->index('check_in_time', 'attendances_check_in_time_index');
            }

            // Status filtering
            if (!$this->indexExists('attendances', 'attendances_status_index')) {
                $table->index('status', 'attendances_status_index');
            }

            // Composite index for employee + date (VERY COMMON)
            if (!$this->indexExists('attendances', 'attendances_employee_date_index')) {
                $table->index(['employee_id', 'date'], 'attendances_employee_date_index');
            }

            // Composite for date range + employee
            if (!$this->indexExists('attendances', 'attendances_date_employee_status_index')) {
                $table->index(['date', 'employee_id', 'status'], 'attendances_date_employee_status_index');
            }

            // Location verification queries (only if location_id column exists)
            if (Schema::hasColumn('attendances', 'location_id') && !$this->indexExists('attendances', 'attendances_location_id_index')) {
                $table->index('location_id', 'attendances_location_id_index');
            }
        });

        // Monthly schedules indexes
        Schema::table('monthly_schedules', function (Blueprint $table) {
            // Month and year queries
            if (!$this->indexExists('monthly_schedules', 'monthly_schedules_month_year_index')) {
                $table->index(['month', 'year'], 'monthly_schedules_month_year_index');
            }

            // Active schedules
            if (!$this->indexExists('monthly_schedules', 'monthly_schedules_active_index')) {
                $table->index('is_active', 'monthly_schedules_active_index');
            }

            // Location filtering
            if (!$this->indexExists('monthly_schedules', 'monthly_schedules_location_id_index')) {
                $table->index('location_id', 'monthly_schedules_location_id_index');
            }

            // Date range queries
            if (!$this->indexExists('monthly_schedules', 'monthly_schedules_dates_index')) {
                $table->index(['start_date', 'end_date'], 'monthly_schedules_dates_index');
            }
        });

        // Employee monthly schedules (junction table)
        Schema::table('employee_monthly_schedules', function (Blueprint $table) {
            // Employee lookups
            if (!$this->indexExists('employee_monthly_schedules', 'emp_monthly_sch_employee_index')) {
                $table->index('employee_id', 'emp_monthly_sch_employee_index');
            }

            // Schedule lookups
            if (!$this->indexExists('employee_monthly_schedules', 'emp_monthly_sch_schedule_index')) {
                $table->index('monthly_schedule_id', 'emp_monthly_sch_schedule_index');
            }

            // Composite for joins
            if (!$this->indexExists('employee_monthly_schedules', 'emp_monthly_sch_composite_index')) {
                $table->index(
                    ['employee_id', 'monthly_schedule_id'],
                    'emp_monthly_sch_composite_index'
                );
            }
        });

        // Locations table
        Schema::table('locations', function (Blueprint $table) {
            // Active locations
            if (!$this->indexExists('locations', 'locations_active_index')) {
                $table->index('is_active', 'locations_active_index');
            }
        });

        // Holidays table
        if (Schema::hasTable('holidays')) {
            Schema::table('holidays', function (Blueprint $table) {
                // Date queries
                if (Schema::hasColumn('holidays', 'date') && !$this->indexExists('holidays', 'holidays_date_index')) {
                    $table->index('date', 'holidays_date_index');
                }

                // Active holidays
                if (Schema::hasColumn('holidays', 'is_active') && !$this->indexExists('holidays', 'holidays_active_index')) {
                    $table->index('is_active', 'holidays_active_index');
                }

                // Year filtering
                if (Schema::hasColumn('holidays', 'year') && !$this->indexExists('holidays', 'holidays_year_index')) {
                    $table->index('year', 'holidays_year_index');
                }
            });
        }

        // Log completion - performance indexes added successfully
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order

        if (Schema::hasTable('holidays')) {
            Schema::table('holidays', function (Blueprint $table) {
                $table->dropIndex('holidays_date_index');
                $table->dropIndex('holidays_active_index');
                if (Schema::hasColumn('holidays', 'year')) {
                    $table->dropIndex('holidays_year_index');
                }
            });
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_active_index');
        });

        Schema::table('employee_monthly_schedules', function (Blueprint $table) {
            $table->dropIndex('emp_monthly_sch_employee_index');
            $table->dropIndex('emp_monthly_sch_schedule_index');
            $table->dropIndex('emp_monthly_sch_composite_index');
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->dropIndex('monthly_schedules_month_year_index');
            $table->dropIndex('monthly_schedules_active_index');
            $table->dropIndex('monthly_schedules_location_id_index');
            $table->dropIndex('monthly_schedules_dates_index');
        });

        Schema::table('attendances', function (Blueprint $table) {
            try { $table->dropIndex('attendances_employee_id_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('attendances_date_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('attendances_check_in_time_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('attendances_status_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('attendances_employee_date_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('attendances_date_employee_status_index'); } catch (\Exception $e) {}
            try { $table->dropIndex('attendances_location_id_index'); } catch (\Exception $e) {}
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_employee_id_index');
            $table->dropIndex('employees_user_id_index');
            $table->dropIndex('employees_active_index');
            $table->dropIndex('employees_user_active_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
            $table->dropIndex('users_active_index');
            $table->dropIndex('users_email_active_index');
        });
    }

    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $schemaBuilder = $connection->getSchemaBuilder();

            // Get all indexes for the table
            $indexes = $schemaBuilder->getIndexes($table);

            // Check if index name exists
            foreach ($indexes as $idx) {
                if ($idx['name'] === $index) {
                    return true;
                }
            }

            return false;

        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            // Better to fail trying to create than skip
            return false;
        }
    }
};
