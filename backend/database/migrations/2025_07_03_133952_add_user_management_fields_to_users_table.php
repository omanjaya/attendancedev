<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('email');
            $table->timestamp('last_login_at')->nullable()->after('updated_at');

            // Add indexes for better performance
            $table->index('is_active');
            $table->index('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['last_login_at']);
            $table->dropColumn(['is_active', 'last_login_at']);
        });
    }
};
