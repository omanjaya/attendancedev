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
        // Make subject_id nullable - teaching schedules may not always have a subject
        DB::statement('ALTER TABLE teaching_schedules ALTER COLUMN subject_id DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This will fail if there are null values in subject_id
        DB::statement('ALTER TABLE teaching_schedules ALTER COLUMN subject_id SET NOT NULL');
    }
};
