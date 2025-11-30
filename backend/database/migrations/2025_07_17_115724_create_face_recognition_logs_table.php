<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_recognition_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action', 50); // register, update, delete, verify_success, verify_failed
            $table->uuid('employee_id')->nullable();
            $table->json('data')->nullable(); // Contains similarity scores, confidence, liveness data
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['employee_id', 'action']);
            $table->index('created_at');

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_recognition_logs');
    }
};
