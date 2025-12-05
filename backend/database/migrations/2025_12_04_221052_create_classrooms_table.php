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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // e.g., "X IPA 1"
            $table->integer('grade_level'); // 10, 11, 12
            $table->string('major')->nullable(); // IPA, IPS, etc.
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
