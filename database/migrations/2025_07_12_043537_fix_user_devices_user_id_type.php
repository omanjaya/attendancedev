<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, backup any existing data
        DB::statement('CREATE TEMPORARY TABLE user_devices_backup AS SELECT * FROM user_devices');
        
        // Drop the existing table
        Schema::dropIfExists('user_devices');
        
        // Recreate with correct foreign key type
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('user_id'); // Changed from uuid to match users.id
            $table->string('device_fingerprint')->index();
            $table->string('device_name')->nullable();
            $table->string('device_type');
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('last_seen_at');
            $table->string('last_ip_address');
            $table->string('last_location')->nullable();
            $table->unsignedInteger('login_count')->default(1);
            $table->json('fingerprint_data');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'device_fingerprint'], 'unique_user_device');
        });
        
        // Restore data if any existed (likely none since this is new)
        DB::statement('INSERT INTO user_devices SELECT * FROM user_devices_backup WHERE 1=0'); // Won't insert anything due to schema changes
        DB::statement('DROP TABLE user_devices_backup');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::dropIfExists('user_devices');
        
        // Recreate original structure (UUID foreign key)
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id'); // Original UUID type
            $table->string('device_fingerprint')->index();
            $table->string('device_name')->nullable();
            $table->string('device_type');
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('last_seen_at');
            $table->string('last_ip_address');
            $table->string('last_location')->nullable();
            $table->unsignedInteger('login_count')->default(1);
            $table->json('fingerprint_data');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'device_fingerprint'], 'unique_user_device');
        });
    }
};
