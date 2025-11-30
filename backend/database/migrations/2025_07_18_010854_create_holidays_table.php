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
        Schema::create('holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Nama libur (e.g., "Hari Raya Idul Fitri")
            $table->text('description')->nullable(); // Deskripsi detail
            $table->date('date'); // Tanggal libur
            $table->date('end_date')->nullable(); // Untuk libur multi-hari
            $table->enum('type', [
                'public_holiday',      // Libur nasional (Kemerdekaan, dll)
                'religious_holiday',   // Libur keagamaan
                'school_holiday',      // Libur sekolah (liburan semester)
                'substitute_holiday',   // Cuti bersama
            ]);
            $table->enum('status', ['active', 'cancelled', 'moved'])->default('active');
            $table->boolean('is_recurring')->default(false); // Apakah berulang setiap tahun
            $table->json('recurring_pattern')->nullable(); // Pattern untuk recurring holidays
            $table->json('affected_roles')->nullable(); // Role mana yang libur (all, teachers_only, etc)
            $table->string('source')->nullable(); // Sumber data (manual, government_api, etc)
            $table->string('color', 7)->default('#dc3545'); // Warna untuk calendar display
            $table->boolean('is_paid')->default(true); // Apakah libur dibayar
            $table->json('metadata')->nullable(); // Data tambahan
            $table->timestamps();

            // Indexes for performance
            $table->index('date');
            $table->index('type');
            $table->index('status');
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
