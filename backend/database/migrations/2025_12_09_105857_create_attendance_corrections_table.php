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
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('attendance_id')->nullable();
            $table->date('correction_date')->comment('Date for which correction is requested');
            
            // What type of correction
            $table->enum('correction_type', [
                'check_in',      // Correct check-in time
                'check_out',     // Correct check-out time
                'both',          // Correct both times
                'add_missing',   // Add missing attendance record
                'delete',        // Request to delete attendance
            ])->default('both');
            
            // Original values (for reference)
            $table->time('original_check_in')->nullable();
            $table->time('original_check_out')->nullable();
            
            // Requested corrections
            $table->time('requested_check_in')->nullable();
            $table->time('requested_check_out')->nullable();
            
            // Reason and supporting documents
            $table->text('reason');
            $table->string('supporting_document')->nullable()->comment('Path to uploaded document/proof');
            
            // Status workflow
            $table->enum('status', [
                'pending',       // Waiting for approval
                'approved',      // Approved by admin
                'rejected',      // Rejected by admin
                'cancelled',     // Cancelled by employee
            ])->default('pending');
            
            // Approval details
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign keys
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
            
            // Indexes
            $table->index(['employee_id', 'correction_date']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
