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
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->enum('type', ['earning', 'deduction', 'bonus']);
            $table->string('category', 50);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('quantity', 8, 2)->nullable();
            $table->decimal('rate', 8, 2)->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table
                ->enum('calculation_method', ['fixed', 'percentage', 'hourly', 'daily', 'computed'])
                ->default('fixed');
            $table->jsonb('reference_data')->default('{}');
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');

            // Indexes
            $table->index('payroll_id');
            $table->index('type');
            $table->index('category');
            $table->index(['type', 'category']);
            $table->index('is_taxable');
            $table->index('is_statutory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
