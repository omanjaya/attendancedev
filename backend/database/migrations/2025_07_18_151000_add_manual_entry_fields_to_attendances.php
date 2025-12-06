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
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_manual_entry')->default(false);
            $table->text('manual_entry_reason')->nullable();
            $table->unsignedBigInteger('manual_entry_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->foreign('manual_entry_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['is_manual_entry', 'date']);
            $table->index(['manual_entry_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['manual_entry_by']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['is_manual_entry', 'date']);
            $table->dropIndex(['manual_entry_by', 'created_at']);
            $table->dropColumn(['is_manual_entry', 'manual_entry_reason', 'manual_entry_by', 'updated_by']);
        });
    }
};