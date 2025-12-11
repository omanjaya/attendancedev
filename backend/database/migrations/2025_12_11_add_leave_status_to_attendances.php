<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the check constraint to include 'leave' and 'holiday' status
        DB::statement("
            ALTER TABLE attendances 
            DROP CONSTRAINT IF EXISTS attendances_status_check
        ");

        DB::statement("
            ALTER TABLE attendances 
            ADD CONSTRAINT attendances_status_check 
            CHECK (status IN ('present', 'absent', 'late', 'early_departure', 'incomplete', 'leave', 'holiday'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE attendances 
            DROP CONSTRAINT IF EXISTS attendances_status_check
        ");

        DB::statement("
            ALTER TABLE attendances 
            ADD CONSTRAINT attendances_status_check 
            CHECK (status IN ('present', 'absent', 'late', 'early_departure', 'incomplete'))
        ");
    }
};
