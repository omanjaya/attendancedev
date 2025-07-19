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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('employee_id', 50)->unique();
            $table->enum('employee_type', ['permanent', 'honorary', 'staff']);
            $table->string('full_name', 255);
            $table->string('phone', 20)->nullable();
            $table->date('hire_date');
            $table->enum('salary_type', ['hourly', 'monthly', 'fixed']);
            $table->decimal('salary_amount', 10, 2)->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('location_id')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('employee_type');
            $table->index('is_active');
            $table->index('full_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
