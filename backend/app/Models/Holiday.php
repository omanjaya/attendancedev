<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'date',
        'end_date',
        'type',
        'status',
        'is_recurring',
        'recurring_pattern',
        'affected_roles',
        'source',
        'color',
        'is_paid',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_paid' => 'boolean',
        'recurring_pattern' => 'array',
        'affected_roles' => 'array',
        'metadata' => 'array',
    ];

    // Holiday types
    const TYPE_PUBLIC = 'public_holiday';

    const TYPE_RELIGIOUS = 'religious_holiday';

    const TYPE_SCHOOL = 'school_holiday';

    const TYPE_SUBSTITUTE = 'substitute_holiday';

    // Holiday status
    const STATUS_ACTIVE = 'active';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_MOVED = 'moved';

    /**
     * Scope for active holidays
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for holidays in date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate])
                ->orWhere(function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->whereNotNull('end_date')
                        ->where('date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                });
        });
    }

    /**
     * Scope for holidays by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for recurring holidays
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Check if holiday is multi-day
     */
    public function isMultiDay(): bool
    {
        return ! is_null($this->end_date) && $this->date->lt($this->end_date);
    }

    /**
     * Get duration in days
     */
    public function getDurationInDays(): int
    {
        if ($this->isMultiDay()) {
            return $this->date->diffInDays($this->end_date) + 1;
        }

        return 1;
    }

    /**
     * Check if holiday affects specific role
     */
    public function affectsRole(string $role): bool
    {
        if (empty($this->affected_roles)) {
            return true; // Affects all roles if not specified
        }

        return in_array($role, $this->affected_roles) || in_array('all', $this->affected_roles);
    }

    /**
     * Get all dates covered by this holiday
     */
    public function getAllDates(): array
    {
        $dates = [];
        $current = $this->date->copy();
        $end = $this->end_date ?? $this->date;

        while ($current->lte($end)) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Generate next occurrence for recurring holiday
     */
    public function generateNextOccurrence(int $year): ?Holiday
    {
        if (! $this->is_recurring || empty($this->recurring_pattern)) {
            return null;
        }

        $pattern = $this->recurring_pattern;
        $newDate = null;

        switch ($pattern['type'] ?? 'yearly') {
            case 'yearly':
                // Same month and day, different year
                $newDate = Carbon::create($year, $this->date->month, $this->date->day);
                break;

            case 'relative':
                // E.g., "First Monday of September"
                if (isset($pattern['month'], $pattern['week'], $pattern['day'])) {
                    $newDate = Carbon::create($year, $pattern['month'], 1)
                        ->nthOfMonth($pattern['week'], $pattern['day']);
                }
                break;

            case 'islamic':
            case 'lunar':
                // Would need Islamic calendar calculation
                // For now, skip automatic generation
                break;
        }

        if ($newDate && $newDate->year === $year) {
            return new self([
                'name' => $this->name,
                'description' => $this->description,
                'date' => $newDate,
                'end_date' => $this->end_date ? $newDate->copy()->addDays($this->getDurationInDays() - 1) : null,
                'type' => $this->type,
                'status' => self::STATUS_ACTIVE,
                'is_recurring' => true,
                'recurring_pattern' => $this->recurring_pattern,
                'affected_roles' => $this->affected_roles,
                'source' => 'auto_generated',
                'color' => $this->color,
                'is_paid' => $this->is_paid,
                'metadata' => $this->metadata,
            ]);
        }

        return null;
    }

    /**
     * Get holidays for a specific date
     */
    public static function getHolidaysForDate(Carbon $date): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->where(function ($query) use ($date) {
                $query->where('date', $date->format('Y-m-d'))
                    ->orWhere(function ($subQuery) use ($date) {
                        $subQuery->whereNotNull('end_date')
                            ->where('date', '<=', $date->format('Y-m-d'))
                            ->where('end_date', '>=', $date->format('Y-m-d'));
                    });
            })
            ->get();
    }

    /**
     * Check if a specific date is a holiday
     */
    public static function isHoliday(Carbon $date, ?string $role = null): bool
    {
        $holidays = self::getHolidaysForDate($date);

        if ($role) {
            $holidays = $holidays->filter(function ($holiday) use ($role) {
                return $holiday->affectsRole($role);
            });
        }

        return $holidays->isNotEmpty();
    }

    /**
     * Get working days between two dates (excluding holidays)
     */
    public static function getWorkingDaysBetween(Carbon $startDate, Carbon $endDate, ?string $role = null): int
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            // Skip weekends (assuming Saturday-Sunday weekend)
            if (! $current->isWeekend()) {
                // Check if it's not a holiday
                if (! self::isHoliday($current, $role)) {
                    $workingDays++;
                }
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Get holiday types array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PUBLIC => 'Libur Nasional',
            self::TYPE_RELIGIOUS => 'Libur Keagamaan',
            self::TYPE_SCHOOL => 'Libur Sekolah',
            self::TYPE_SUBSTITUTE => 'Cuti Bersama',
        ];
    }

    /**
     * Get holiday status array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_CANCELLED => 'Dibatalkan',
            self::STATUS_MOVED => 'Dipindah',
        ];
    }

    /**
     * Format for display
     */
    public function getFormattedDateAttribute(): string
    {
        if ($this->isMultiDay()) {
            return $this->date->format('d M Y').' - '.$this->end_date->format('d M Y');
        }

        return $this->date->format('d M Y');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }
}
