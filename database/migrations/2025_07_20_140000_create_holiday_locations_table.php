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
        Schema::create('holiday_locations', function (Blueprint $table) {
            $table->id();
            $table->uuid('national_holiday_id');
            $table->uuid('location_id');
            $table->timestamps();

            $table->foreign('national_holiday_id')->references('id')->on('national_holidays')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            
            $table->unique(['national_holiday_id', 'location_id']);
            $table->index(['national_holiday_id']);
            $table->index(['location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holiday_locations');
    }
};