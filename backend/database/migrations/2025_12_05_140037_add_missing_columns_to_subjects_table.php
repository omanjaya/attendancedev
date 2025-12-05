<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            // Add missing columns for SoftDeletes and additional fields
            if (!Schema::hasColumn('subjects', 'deleted_at')) {
                $table->softDeletes();
            }
            if (!Schema::hasColumn('subjects', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('subjects', 'color')) {
                $table->string('color', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['is_active', 'color']);
        });
    }
};
