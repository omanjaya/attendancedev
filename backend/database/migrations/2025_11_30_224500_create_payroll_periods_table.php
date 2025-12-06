<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['monthly', 'weekly', 'biweekly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date');
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
            $table->text('notes')->nullable();

            // Aggregated totals (optional but good for performance)
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->integer('total_employees')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->uuid('payroll_period_id')->nullable();
            $table->foreign('payroll_period_id')->references('id')->on('payroll_periods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['payroll_period_id']);
            $table->dropColumn('payroll_period_id');
        });

        Schema::dropIfExists('payroll_periods');
    }
};
