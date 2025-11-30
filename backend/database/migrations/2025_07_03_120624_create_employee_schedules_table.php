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
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('period_id')->constrained()->onDelete('cascade');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_id');
            $table->index('period_id');
            $table->index('effective_date');
            $table->index('is_active');
            $table->unique(['employee_id', 'period_id', 'effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_schedules');
    }
};
