<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'schedule_mode',
        'default_start_time',
        'default_end_time',
        'work_days',
        'late_tolerance_minutes',
        'require_schedule_for_attendance',
        'can_override_by_teaching',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_start_time' => 'datetime:H:i',
        'default_end_time' => 'datetime:H:i',
        'work_days' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'require_schedule_for_attendance' => 'boolean',
        'can_override_by_teaching' => 'boolean',
        'late_tolerance_minutes' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $attributes = [
        'schedule_mode' => 'fixed',
        'late_tolerance_minutes' => 15,
        'require_schedule_for_attendance' => true,
        'can_override_by_teaching' => false,
        'is_active' => true,
        'sort_order' => 0,
    ];

    // ========== RELATIONSHIPS ==========

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ========== HELPER METHODS ==========

    /**
     * Check if this type uses fixed schedule (jam tetap)
     */
    public function isFixed(): bool
    {
        return $this->schedule_mode === 'fixed';
    }

    /**
     * Check if this type uses flexible schedule (jam mengajar)
     */
    public function isFlexible(): bool
    {
        return $this->schedule_mode === 'flexible';
    }

    /**
     * Check if a specific feature is enabled for this type
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get all available features
     */
    public static function availableFeatures(): array
    {
        return [
            'can_request_leave' => 'Dapat mengajukan cuti',
            'can_view_payroll' => 'Dapat melihat slip gaji',
            'can_substitute' => 'Dapat menjadi guru pengganti',
            'can_access_reports' => 'Dapat mengakses laporan',
            'can_overtime' => 'Dapat melakukan lembur',
        ];
    }

    /**
     * Get formatted working hours
     */
    public function getFormattedWorkingHoursAttribute(): ?string
    {
        if (!$this->default_start_time || !$this->default_end_time) {
            return null;
        }

        return $this->default_start_time->format('H:i') . ' - ' . $this->default_end_time->format('H:i');
    }

    /**
     * Get schedule mode label
     */
    public function getScheduleModeLabelAttribute(): string
    {
        return match ($this->schedule_mode) {
            'fixed' => 'Tetap',
            'flexible' => 'Fleksibel',
            default => ucfirst($this->schedule_mode),
        };
    }
}
