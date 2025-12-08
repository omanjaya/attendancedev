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
        // Departments table (Unit Kerja untuk Staff)
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // Positions table (Posisi/Jabatan untuk Staff)
        Schema::create('positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'sort_order']);
        });

        // Add new columns to employees table
        Schema::table('employees', function (Blueprint $table) {
            // Subject for teachers (Guru)
            if (!Schema::hasColumn('employees', 'subject_id')) {
                $table->uuid('subject_id')->nullable()->after('employee_type_id');
                $table->foreign('subject_id')
                    ->references('id')
                    ->on('subjects')
                    ->nullOnDelete();
            }

            // Department for staff (Unit Kerja)
            if (!Schema::hasColumn('employees', 'department_id')) {
                $table->uuid('department_id')->nullable()->after('subject_id');
                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->nullOnDelete();
            }

            // Position for staff (Posisi/Jabatan dinamis)
            if (!Schema::hasColumn('employees', 'position_id')) {
                $table->uuid('position_id')->nullable()->after('department_id');
                $table->foreign('position_id')
                    ->references('id')
                    ->on('positions')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'subject_id')) {
                $table->dropForeign(['subject_id']);
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('employees', 'department_id')) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('employees', 'position_id')) {
                $table->dropForeign(['position_id']);
                $table->dropColumn('position_id');
            }
        });

        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
