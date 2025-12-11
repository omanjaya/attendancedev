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
        Schema::create('payroll_formulas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->enum('type', ['earning', 'deduction', 'bonus'])->default('earning');
            $table->enum('formula_type', ['fixed', 'percentage', 'conditional', 'custom'])->default('fixed');
            $table->text('formula_expression')->nullable();
            $table->string('base_field', 50)->nullable()->comment('Field to calculate from: base_salary, gross_salary, worked_days, etc.');
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->decimal('percentage_rate', 8, 4)->nullable()->comment('Percentage rate for percentage formulas');
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Order of calculation, higher = later');
            $table->string('category', 50)->default('other')->comment('Matches PayrollItem categories');
            $table->text('description')->nullable();
            $table->json('conditions')->nullable()->comment('JSON conditions for conditional formulas');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_formulas');
    }
};
