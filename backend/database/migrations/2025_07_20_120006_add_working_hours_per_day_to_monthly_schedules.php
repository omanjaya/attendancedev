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
            $table->json('working_hours_per_day')->nullable();
            $table->string('working_hours_template')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->dropColumn(['working_hours_per_day', 'working_hours_template']);
        });
    }
};