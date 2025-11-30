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
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');

            // Security notification preferences
            $table->boolean('new_device_login_email')->default(true);
            $table->boolean('new_device_login_browser')->default(true);
            $table->boolean('failed_login_attempts_email')->default(true);
            $table->boolean('failed_login_attempts_browser')->default(true);
            $table->boolean('suspicious_activity_email')->default(true);
            $table->boolean('suspicious_activity_browser')->default(true);
            $table->boolean('account_locked_email')->default(true);
            $table->boolean('account_locked_browser')->default(true);
            $table->boolean('password_changed_email')->default(true);
            $table->boolean('password_changed_browser')->default(true);
            $table->boolean('two_factor_changes_email')->default(true);
            $table->boolean('two_factor_changes_browser')->default(true);
            $table->boolean('device_trusted_email')->default(true);
            $table->boolean('device_trusted_browser')->default(false);
            $table->boolean('admin_access_email')->default(true);
            $table->boolean('admin_access_browser')->default(true);

            // System notification preferences
            $table->boolean('attendance_reminders_email')->default(true);
            $table->boolean('attendance_reminders_browser')->default(true);
            $table->boolean('leave_status_email')->default(true);
            $table->boolean('leave_status_browser')->default(true);
            $table->boolean('payroll_notifications_email')->default(true);
            $table->boolean('payroll_notifications_browser')->default(false);
            $table->boolean('system_maintenance_email')->default(false);
            $table->boolean('system_maintenance_browser')->default(true);

            // Notification timing preferences
            $table->json('quiet_hours')->nullable(); // {start: "22:00", end: "08:00", timezone: "UTC"}
            $table->json('digest_frequency')->nullable(); // {security: "immediate", system: "daily"}

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
