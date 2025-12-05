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
        Schema::create('employee_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                    // e.g., "Pegawai Tetap", "Guru Honor"
            $table->string('code')->unique();          // e.g., "tetap", "honor"
            $table->text('description')->nullable();
            
            // Schedule behavior
            $table->enum('schedule_mode', ['fixed', 'flexible'])->default('fixed');
            // fixed = Jam kerja tetap sesuai jadwal bulanan
            // flexible = Override oleh teaching schedule (untuk honor)
            
            // Default working hours (untuk pegawai tetap)
            $table->time('default_start_time')->nullable();  // 07:30
            $table->time('default_end_time')->nullable();    // 15:30
            
            // Attendance rules
            $table->integer('late_tolerance_minutes')->default(15);
            $table->boolean('require_schedule_for_attendance')->default(true);
            $table->boolean('can_override_by_teaching')->default(false);
            
            // Permissions/Features (JSON array of enabled features)
            $table->json('features')->nullable();
            // e.g., ["can_request_leave", "can_view_payroll", "can_substitute"]
            
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_types');
    }
};
