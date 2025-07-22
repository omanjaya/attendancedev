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
        // Add performance indexes to employees table
        Schema::table('employees', function (Blueprint $table) {
            // Note: employee_id column already has unique index, skip
            
            // Use is_active instead of status (which doesn't exist)
            if (Schema::hasColumn('employees', 'is_active')) {
                $table->index('is_active', 'idx_employees_is_active');
            }
            if (Schema::hasColumn('employees', 'location_id') && Schema::hasColumn('employees', 'is_active')) {
                $table->index(['location_id', 'is_active'], 'idx_employees_location_active');
            }
            if (Schema::hasColumn('employees', 'user_id')) {
                $table->index('user_id', 'idx_employees_user_id');
            }
        });

        // Add performance indexes to attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('date', 'idx_attendances_date');
            $table->index(['employee_id', 'date'], 'idx_attendances_employee_date');
            $table->index(['date', 'status'], 'idx_attendances_date_status');
            $table->index('check_in_time', 'idx_attendances_check_in');
            $table->index('check_out_time', 'idx_attendances_check_out');
        });

        // Add performance indexes to leaves table
        Schema::table('leaves', function (Blueprint $table) {
            $table->index('start_date', 'idx_leaves_start_date');
            $table->index('end_date', 'idx_leaves_end_date');
            $table->index(['start_date', 'end_date'], 'idx_leaves_date_range');
            $table->index(['employee_id', 'status'], 'idx_leaves_employee_status');
            $table->index('status', 'idx_leaves_status');
        });

        // Add performance indexes to payrolls table
        Schema::table('payrolls', function (Blueprint $table) {
            $table->index('period', 'idx_payrolls_period');
            $table->index(['employee_id', 'period'], 'idx_payrolls_employee_period');
            $table->index('status', 'idx_payrolls_status');
        });

        // Add performance indexes to user_devices table
        Schema::table('user_devices', function (Blueprint $table) {
            $table->index('user_id', 'idx_user_devices_user_id');
            $table->index('device_fingerprint', 'idx_user_devices_fingerprint');
            $table->index('is_trusted', 'idx_user_devices_trusted');
        });

        // Add performance indexes to audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('user_id', 'idx_audit_logs_user_id');
            $table->index('event_type', 'idx_audit_logs_event_type');
            $table->index('created_at', 'idx_audit_logs_created_at');
            $table->index(['user_id', 'event_type'], 'idx_audit_logs_user_event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from employees table
        try {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex('idx_employees_is_active');
                $table->dropIndex('idx_employees_location_active');
                $table->dropIndex('idx_employees_user_id');
            });
        } catch (\Exception $e) {
            // Indexes might not exist, continue
        }

        // Remove indexes from attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_date');
            $table->dropIndex('idx_attendances_employee_date');
            $table->dropIndex('idx_attendances_date_status');
            $table->dropIndex('idx_attendances_check_in');
            $table->dropIndex('idx_attendances_check_out');
        });

        // Remove indexes from leaves table
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex('idx_leaves_start_date');
            $table->dropIndex('idx_leaves_end_date');
            $table->dropIndex('idx_leaves_date_range');
            $table->dropIndex('idx_leaves_employee_status');
            $table->dropIndex('idx_leaves_status');
        });

        // Remove indexes from payrolls table
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('idx_payrolls_period');
            $table->dropIndex('idx_payrolls_employee_period');
            $table->dropIndex('idx_payrolls_status');
        });

        // Remove indexes from user_devices table
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropIndex('idx_user_devices_user_id');
            $table->dropIndex('idx_user_devices_fingerprint');
            $table->dropIndex('idx_user_devices_trusted');
        });

        // Remove indexes from audit_logs table
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_user_id');
            $table->dropIndex('idx_audit_logs_event_type');
            $table->dropIndex('idx_audit_logs_created_at');
            $table->dropIndex('idx_audit_logs_user_event');
        });
    }
};
