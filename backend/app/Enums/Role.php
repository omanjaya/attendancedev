<?php

namespace App\Enums;

/**
 * Centralized Role Constants
 *
 * Best Practice: Single source of truth for all role names
 * Format: kebab-case for consistency with frontend
 */
enum Role: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case KEPALA_SEKOLAH = 'kepala-sekolah';
    case GURU = 'guru';
    case PEGAWAI = 'pegawai';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::KEPALA_SEKOLAH => 'Kepala Sekolah',
            self::GURU => 'Guru',
            self::PEGAWAI => 'Pegawai',
        };
    }

    /**
     * Get all role values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if role is admin-level
     */
    public function isAdmin(): bool
    {
        return in_array($this, [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::KEPALA_SEKOLAH,
        ]);
    }

    /**
     * Check if role is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    /**
     * Get role from string (case-insensitive, handles spaces and underscores)
     */
    public static function fromString(string $role): ?self
    {
        // Normalize: convert to lowercase and replace spaces/underscores with dashes
        $normalized = strtolower(str_replace(['_', ' '], '-', $role));

        return self::tryFrom($normalized);
    }
}
