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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('device_fingerprint')->index();
            $table->string('device_name')->nullable();
            $table->string('device_type'); // desktop, mobile, tablet
            $table->string('browser_name')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamp('trusted_at')->nullable();
            $table->timestamp('last_seen_at');
            $table->string('last_ip_address', 45);
            $table->string('last_location')->nullable();
            $table->integer('login_count')->default(1);
            $table->json('fingerprint_data'); // Store detailed fingerprint components
            $table->json('metadata')->nullable(); // Additional device info
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint']);
            $table->index(['user_id', 'is_trusted']);
            $table->index(['user_id', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
