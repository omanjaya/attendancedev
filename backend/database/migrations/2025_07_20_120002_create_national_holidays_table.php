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
        Schema::create('national_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Holiday information
            $table->string('name'); // e.g., "Hari Kemerdekaan Indonesia"
            $table->date('holiday_date'); // The actual holiday date
            $table->enum('type', [
                'national',     // National holidays (affects all locations)
                'regional',     // Regional holidays (specific to location)
                'religious',    // Religious holidays
                'school',       // School-specific holidays
                'custom'        // Custom holidays set by admin
            ])->default('national');
            
            // Holiday details
            $table->text('description')->nullable(); // Detailed description
            $table->boolean('is_recurring')->default(false); // Annual recurring holiday
            $table->boolean('is_active')->default(true);
            
            // Scope and applicability
            $table->uuid('location_id')->nullable(); // For regional holidays, null for national
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            
            // Recurring holiday configuration
            $table->json('recurrence_config')->nullable(); // {
            //     "frequency": "yearly",
            //     "day_of_month": 17,
            //     "month": 8,
            //     "day_of_week": null,
            //     "week_of_month": null,
            //     "end_date": null,
            //     "exceptions": ["2025-08-17"] // Years to skip
            // }
            
            // Override behavior
            $table->boolean('force_override')->default(true); // Override existing schedules
            $table->boolean('paid_leave')->default(true); // Whether employees get paid
            
            // Administrative fields
            $table->string('source')->default('manual'); // manual, import, system, api
            $table->string('reference_code')->nullable(); // External reference
            $table->json('metadata')->nullable(); // Additional holiday data
            
            // Audit fields
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Performance indexes
            $table->index(['holiday_date', 'is_active']); // Primary date lookup
            $table->index(['type', 'is_active']); // Type-based filtering
            $table->index(['location_id', 'holiday_date']); // Location-specific holidays
            $table->index(['is_recurring', 'is_active']); // Recurring holiday management
            $table->index(['holiday_date', 'type', 'location_id']); // Complex queries
            
            // Year-based partitioning helper (SQLite compatible)
            $table->integer('holiday_year')->storedAs("CAST(strftime('%Y', holiday_date) AS INTEGER)");
            $table->index(['holiday_year', 'type']); // Year-based queries
            
            // Foreign keys for audit
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Prevent duplicate holidays for same date and scope
            $table->unique(['holiday_date', 'type', 'location_id'], 'unique_holiday_date_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_holidays');
    }
};