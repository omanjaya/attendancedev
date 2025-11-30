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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->date('payroll_period_start');
            $table->date('payroll_period_end');
            $table->date('pay_date');
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_bonuses', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->decimal('worked_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('leave_days_taken', 5, 2)->default(0);
            $table->decimal('leave_days_paid', 5, 2)->default(0);
            $table->decimal('leave_days_unpaid', 5, 2)->default(0);
            $table
                ->enum('status', ['draft', 'pending', 'approved', 'processed', 'paid', 'cancelled'])
                ->default('draft');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('employees')->onDelete('set null');

            // Indexes
            $table->index('employee_id');
            $table->index(['payroll_period_start', 'payroll_period_end']);
            $table->index('status');
            $table->index('pay_date');

            // Unique constraint for employee per payroll period
            $table->unique(
                ['employee_id', 'payroll_period_start', 'payroll_period_end'],
                'unique_employee_payroll_period',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
