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
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });

        Schema::table('classrooms', function (Blueprint $table) {
            if (!Schema::hasColumn('classrooms', 'code')) {
                $table->string('code')->nullable()->unique();
            }
            if (!Schema::hasColumn('classrooms', 'capacity')) {
                $table->integer('capacity')->default(30);
            }
            if (!Schema::hasColumn('classrooms', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            // Make grade_level nullable or string if needed, but for now assuming existing schema works or modify here
            // Note: Frontend sends grade_level as string "X", "XI", "XII" but DB expects integer. 
            // We should modify grade_level to be string compatible if possible or map it in controller.
            // Let's modify the column to string to be safe and flexible
            $table->string('grade_level')->change(); 
        });

        Schema::table('periods', function (Blueprint $table) {
            if (!Schema::hasColumn('periods', 'day_of_week')) {
                $table->integer('day_of_week')->nullable()->comment('0=Sunday, 1=Monday, etc');
            }
            if (!Schema::hasColumn('periods', 'period_type')) {
                $table->enum('period_type', ['lesson', 'break', 'other'])->default('lesson');
            }
            if (!Schema::hasColumn('periods', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['is_active']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn(['code', 'capacity', 'is_active']);
             // Reverting grade_level change might be risky if data is converted, so we skip strictly reverting type
        });

        Schema::table('periods', function (Blueprint $table) {
            $table->dropColumn(['day_of_week', 'period_type', 'is_active']);
        });
    }
};
