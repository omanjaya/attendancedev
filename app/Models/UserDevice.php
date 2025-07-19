<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'device_name',
        'device_type',
        'browser_name',
        'browser_version',
        'os_name',
        'os_version',
        'is_trusted',
        'trusted_at',
        'last_seen_at',
        'last_ip_address',
        'last_location',
        'login_count',
        'fingerprint_data',
        'metadata',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'trusted_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'login_count' => 'integer',
        'fingerprint_data' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark device as trusted.
     */
    public function markAsTrusted(): void
    {
        $this->update([
            'is_trusted' => true,
            'trusted_at' => now(),
        ]);
    }

    /**
     * Revoke device trust.
     */
    public function revokeTrust(): void
    {
        $this->update([
            'is_trusted' => false,
            'trusted_at' => null,
        ]);
    }

    /**
     * Update last seen information.
     */
    public function updateLastSeen(string $ipAddress, ?string $location = null): void
    {
        $this->update([
            'last_seen_at' => now(),
            'last_ip_address' => $ipAddress,
            'last_location' => $location,
            'login_count' => $this->login_count + 1,
        ]);
    }

    /**
     * Check if device is recently active.
     */
    public function isRecentlyActive(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subDays(30));
    }

    /**
     * Get device display name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->device_name) {
            return $this->device_name;
        }

        $parts = [];

        if ($this->browser_name) {
            $parts[] = $this->browser_name;
        }

        if ($this->os_name) {
            $parts[] = "on {$this->os_name}";
        }

        if (empty($parts)) {
            return ucfirst($this->device_type).' Device';
        }

        return implode(' ', $parts);
    }

    /**
     * Scope for trusted devices.
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Scope for recently active devices.
     */
    public function scopeRecentlyActive($query)
    {
        return $query->where('last_seen_at', '>', now()->subDays(30));
    }
}
