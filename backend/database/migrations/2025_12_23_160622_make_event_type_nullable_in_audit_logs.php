<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make event_type nullable - already applied via tinker
        DB::statement('ALTER TABLE audit_logs ALTER COLUMN event_type DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE audit_logs ALTER COLUMN event_type SET NOT NULL');
    }
};
