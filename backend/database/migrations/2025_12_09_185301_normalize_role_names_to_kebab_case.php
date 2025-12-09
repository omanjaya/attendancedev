<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Enums\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Convert role names from "Title Case with Spaces" to "kebab-case"
     * for consistency across backend and frontend.
     */
    public function up(): void
    {
        // Map old role names to new kebab-case format
        $roleMap = [
            'Super Admin' => Role::SUPER_ADMIN->value,
            'Admin' => Role::ADMIN->value,
            'Manager' => Role::KEPALA_SEKOLAH->value,
            'Kepala Sekolah' => Role::KEPALA_SEKOLAH->value,
            'Guru' => Role::GURU->value,
            'Pegawai' => Role::PEGAWAI->value,
            'Employee' => Role::PEGAWAI->value,
        ];

        foreach ($roleMap as $oldName => $newName) {
            // Check if old role exists
            $oldRole = DB::table('roles')->where('name', $oldName)->first();
            if (!$oldRole) {
                continue; // Skip if old role doesn't exist
            }

            // Check if new role name already exists
            $existingNewRole = DB::table('roles')->where('name', $newName)->first();

            if ($existingNewRole && $existingNewRole->id !== $oldRole->id) {
                // New role name already exists, merge the roles
                // Move all users from old role to new role
                DB::table('model_has_roles')
                    ->where('role_id', $oldRole->id)
                    ->update(['role_id' => $existingNewRole->id]);

                // Delete old role
                DB::table('roles')->where('id', $oldRole->id)->delete();
            } else {
                // Simply rename the role
                DB::table('roles')
                    ->where('id', $oldRole->id)
                    ->update(['name' => $newName]);
            }
        }

        // Update users.role column if exists (legacy column)
        if (Schema::hasColumn('users', 'role')) {
            foreach ($roleMap as $oldName => $newName) {
                DB::table('users')
                    ->where('role', $oldName)
                    ->update(['role' => $newName]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Convert back to Title Case with Spaces
     */
    public function down(): void
    {
        // Reverse map
        $roleMap = [
            Role::SUPER_ADMIN->value => 'Super Admin',
            Role::ADMIN->value => 'Admin',
            Role::KEPALA_SEKOLAH->value => 'Kepala Sekolah',
            Role::GURU->value => 'Guru',
            Role::PEGAWAI->value => 'Pegawai',
        ];

        foreach ($roleMap as $newName => $oldName) {
            // Update roles table
            DB::table('roles')
                ->where('name', $newName)
                ->update(['name' => $oldName]);
        }

        // Update users.role column if exists
        if (Schema::hasColumn('users', 'role')) {
            foreach ($roleMap as $newName => $oldName) {
                DB::table('users')
                    ->where('role', $newName)
                    ->update(['role' => $oldName]);
            }
        }
    }
};
