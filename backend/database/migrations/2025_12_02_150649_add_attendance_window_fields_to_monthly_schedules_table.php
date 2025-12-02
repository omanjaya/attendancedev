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
        Schema::table('monthly_schedules', function (Blueprint $table) {
            // Window absen masuk (check-in time window)
            $table->time('checkin_start_time')->nullable()->after('default_end_time');
            $table->time('checkin_end_time')->nullable()->after('checkin_start_time');

            // Window absen pulang (check-out time window)
            $table->time('checkout_start_time')->nullable()->after('checkin_end_time');
            $table->time('checkout_end_time')->nullable()->after('checkout_start_time');

            // Array tanggal hari kerja spesifik (e.g., ["2025-02-03", "2025-02-04", ...])
            $table->json('working_days')->nullable()->after('checkout_end_time');

            // Total hari kerja (calculated field)
            $table->integer('total_working_days')->default(0)->after('working_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'checkin_start_time',
                'checkin_end_time',
                'checkout_start_time',
                'checkout_end_time',
                'working_days',
                'total_working_days'
            ]);
        });
    }
};
