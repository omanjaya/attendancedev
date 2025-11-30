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
        // Time Slots (Jam Pelajaran)
        Schema::create('time_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50); // "Jam 1", "Jam 2", etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('order')->index(); // Urutan jam (1, 2, 3, dst)
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Break time, special notes
            $table->timestamps();

            $table->index(['is_active', 'order']);
        });

        // Academic Classes (Kelas Akademik)
        Schema::create('academic_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100); // "X-IPA-1", "XI-IPS-2"
            $table->string('grade_level', 10); // "X", "XI", "XII"
            $table->string('major', 50)->nullable(); // "IPA", "IPS", "Bahasa"
            $table->string('class_number', 10); // "1", "2", "3"
            $table->integer('capacity')->default(30);
            $table->string('room', 50)->nullable(); // Default classroom
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['grade_level', 'major', 'is_active']);
            $table->unique(['grade_level', 'major', 'class_number']);
        });

        // Subjects (Mata Pelajaran)
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique(); // "MTK", "BIO", "FIS"
            $table->string('name', 100); // "Matematika", "Biologi"
            $table->string('category', 50)->nullable(); // "Exact", "Social", "Language"
            $table->integer('weekly_hours')->default(2); // Jam per minggu
            $table->integer('max_meetings_per_week')->default(3); // Max pertemuan per minggu
            $table->boolean('requires_lab')->default(false);
            $table->string('color', 7)->default('#3B82F6'); // Color for UI
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // Teacher Subjects (Guru pengampu mata pelajaran)
        Schema::create('teacher_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id'); // References employees table
            $table->uuid('subject_id');
            $table->boolean('is_primary')->default(false); // Guru utama mata pelajaran
            $table->integer('max_hours_per_week')->default(24);
            $table->json('competencies')->nullable(); // Sertifikasi, keahlian khusus
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->unique(['employee_id', 'subject_id']);
            $table->index(['employee_id', 'is_active']);
        });

        // Weekly Schedules (Jadwal Mingguan)
        Schema::create('weekly_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('academic_class_id');
            $table->uuid('subject_id');
            $table->uuid('employee_id'); // Teacher
            $table->uuid('time_slot_id');
            $table->enum('day_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
            ]);
            $table->string('room', 50)->nullable();
            $table->date('effective_from'); // Tanggal mulai berlaku
            $table->date('effective_until')->nullable(); // Tanggal berakhir (null = selamanya)
            $table->boolean('is_locked')->default(false); // Kunci jadwal
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Notes, special requirements
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            // Constraints
            $table
                ->foreign('academic_class_id')
                ->references('id')
                ->on('academic_classes')
                ->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');

            // Unique constraint: One schedule per class-timeslot-day combination
            $table->unique(
                ['academic_class_id', 'time_slot_id', 'day_of_week', 'effective_from'],
                'unique_class_schedule',
            );

            // Indexes for performance
            $table->index(['academic_class_id', 'day_of_week']);
            $table->index(['employee_id', 'day_of_week', 'time_slot_id']);
            $table->index(['effective_from', 'effective_until', 'is_active']);
        });

        // Schedule Templates (Template Jadwal untuk copy-paste)
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->json('template_data'); // JSON structure of the schedule
            $table->enum('template_type', ['weekly', 'semester', 'yearly']);
            $table->boolean('is_public')->default(false); // Dapat digunakan user lain
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['template_type', 'is_public']);
        });

        // Schedule Change Logs (Audit Trail)
        Schema::create('schedule_change_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('schedule_id')->nullable(); // null if schedule was deleted
            $table->enum('action', ['create', 'update', 'delete', 'lock', 'unlock', 'bulk_update']);
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->text('reason')->nullable();
            $table->uuid('user_id');
            $table->ipAddress('ip_address')->nullable();
            $table->timestamp('action_timestamp');
            $table->json('metadata')->nullable(); // Browser info, bulk operation details

            $table
                ->foreign('schedule_id')
                ->references('id')
                ->on('weekly_schedules')
                ->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['schedule_id', 'action_timestamp']);
            $table->index(['user_id', 'action_timestamp']);
            $table->index('action_timestamp');
        });

        // Schedule Conflicts (Deteksi konflik)
        Schema::create('schedule_conflicts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('schedule_id_1');
            $table->uuid('schedule_id_2');
            $table->enum('conflict_type', [
                'teacher_double_booking',
                'class_double_booking',
                'room_double_booking',
                'subject_frequency_exceeded',
                'teacher_max_hours_exceeded',
            ]);
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->text('description');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();

            $table
                ->foreign('schedule_id_1')
                ->references('id')
                ->on('weekly_schedules')
                ->onDelete('cascade');
            $table
                ->foreign('schedule_id_2')
                ->references('id')
                ->on('weekly_schedules')
                ->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['is_resolved', 'severity']);
            $table->index('detected_at');
        });

        // Schedule Locks (Penguncian jadwal)
        Schema::create('schedule_locks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('schedule_id');
            $table->enum('lock_type', ['manual', 'automatic', 'system']);
            $table->text('reason');
            $table->timestamp('locked_at');
            $table->timestamp('locked_until')->nullable(); // null = permanent
            $table->uuid('locked_by');
            $table->timestamp('unlocked_at')->nullable();
            $table->uuid('unlocked_by')->nullable();
            $table->text('unlock_reason')->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('schedule_id')->references('id')->on('weekly_schedules')->onDelete('cascade');
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('unlocked_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['schedule_id', 'is_active']);
            $table->index(['locked_until', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_locks');
        Schema::dropIfExists('schedule_conflicts');
        Schema::dropIfExists('schedule_change_logs');
        Schema::dropIfExists('schedule_templates');
        Schema::dropIfExists('weekly_schedules');
        Schema::dropIfExists('teacher_subjects');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('academic_classes');
        Schema::dropIfExists('time_slots');
    }
};
