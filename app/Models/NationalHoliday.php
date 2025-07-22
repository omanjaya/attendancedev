<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Carbon\Carbon;

class NationalHoliday extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'holiday_date',
        'end_date',
        'type',
        'description',
        'is_recurring',
        'is_active',
        'location_id',
        'recurrence_config',
        'force_override',
        'paid_leave',
        'source',
        'reference_code',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'force_override' => 'boolean',
        'paid_leave' => 'boolean',
        'recurrence_config' => 'array',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'type' => 'national',
        'is_recurring' => false,
        'is_active' => true,
        'force_override' => true,
        'paid_leave' => true,
        'source' => 'manual',
        'recurrence_config' => '{}',
        'metadata' => '{}',
    ];

    /**
     * Relationships
     */
    
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'holiday_locations')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function affectedSchedules(): HasMany
    {
        return $this->hasMany(EmployeeMonthlySchedule::class, 'holiday_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'holiday_id');
    }

    /**
     * Scopes
     */
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('holiday_date', $date->toDateString());
    }

    public function scopeForDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('holiday_date', [$startDate->toDateString(), $endDate->toDateString()]);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('holiday_date', $year);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeNational($query)
    {
        return $query->where('type', 'national');
    }

    public function scopeRegional($query)
    {
        return $query->where('type', 'regional');
    }

    public function scopeReligious($query)
    {
        return $query->where('type', 'religious');
    }

    public function scopeSchool($query)
    {
        return $query->where('type', 'school');
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeForLocation($query, string $locationId = null)
    {
        return $query->where(function($q) use ($locationId) {
            $q->whereNull('location_id'); // National holidays
            if ($locationId) {
                $q->orWhere('location_id', $locationId); // Regional holidays
            }
        });
    }

    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->where('holiday_date', '>=', Carbon::today())
                    ->where('holiday_date', '<=', Carbon::today()->addDays($days))
                    ->orderBy('holiday_date');
    }

    /**
     * Accessors & Mutators
     */
    
    public function getFormattedDateAttribute(): string
    {
        return $this->holiday_date->format('d F Y');
    }

    public function getDayNameAttribute(): string
    {
        return $this->holiday_date->format('l');
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->holiday_date->isFuture();
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->holiday_date->isToday();
    }

    public function getDaysUntilAttribute(): int
    {
        return $this->holiday_date->diffInDays(Carbon::today(), false);
    }

    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'national' => 'National Holiday',
            'regional' => 'Regional Holiday',
            'religious' => 'Religious Holiday',
            'school' => 'School Holiday',
            'custom' => 'Custom Holiday'
        ];
        
        return $labels[$this->type] ?? ucfirst($this->type);
    }

    public function getScopeAttribute(): string
    {
        return $this->location_id ? 'Regional' : 'National';
    }

    /**
     * Business Logic Methods
     */
    
    public function applyToSchedules(): int
    {
        if (!$this->force_override) {
            return 0;
        }
        
        $query = EmployeeMonthlySchedule::where('effective_date', $this->holiday_date)
            ->where('status', 'active');
        
        // Apply location scope
        if ($this->location_id) {
            $query->where('location_id', $this->location_id);
        }
        
        $affectedCount = $query->update([
            'status' => 'holiday',
            'is_holiday' => true,
            'override_metadata' => json_encode([
                'override_type' => 'holiday',
                'holiday_id' => $this->id,
                'holiday_name' => $this->name,
                'holiday_type' => $this->type,
                'override_reason' => "Holiday: {$this->name}",
                'override_at' => now(),
                'override_by' => 'system'
            ])
        ]);
        
        // Also update any existing attendance records
        $attendanceQuery = Attendance::where('date', $this->holiday_date);
        
        if ($this->location_id) {
            $attendanceQuery->where('location_id', $this->location_id);
        }
        
        $attendanceQuery->update([
            'holiday_id' => $this->id,
            'schedule_source' => 'holiday_override',
            'status' => 'holiday'
        ]);
        
        return $affectedCount;
    }

    public function removeFromSchedules(): int
    {
        $query = EmployeeMonthlySchedule::where('effective_date', $this->holiday_date)
            ->where('status', 'holiday')
            ->whereJsonContains('override_metadata->holiday_id', $this->id);
        
        if ($this->location_id) {
            $query->where('location_id', $this->location_id);
        }
        
        $revertedCount = 0;
        
        foreach ($query->get() as $schedule) {
            if ($schedule->revertOverride()) {
                $revertedCount++;
            }
        }
        
        // Remove holiday reference from attendance records
        $attendanceQuery = Attendance::where('date', $this->holiday_date)
            ->where('holiday_id', $this->id);
        
        $attendanceQuery->update([
            'holiday_id' => null,
            'schedule_source' => 'base_schedule',
            'status' => null // Will be recalculated
        ]);
        
        return $revertedCount;
    }

    public function generateRecurringHolidays(int $years = 5): array
    {
        if (!$this->is_recurring || !$this->recurrence_config) {
            return [];
        }
        
        $config = $this->recurrence_config;
        $generated = [];
        
        if ($config['frequency'] === 'yearly') {
            $startYear = Carbon::now()->year;
            
            for ($i = 1; $i <= $years; $i++) {
                $year = $startYear + $i;
                
                // Skip if in exceptions
                $dateString = "{$year}-{$config['month']}-{$config['day_of_month']}";
                if (isset($config['exceptions']) && in_array($dateString, $config['exceptions'])) {
                    continue;
                }
                
                // Check if end date is set and we've passed it
                if (isset($config['end_date']) && $dateString > $config['end_date']) {
                    break;
                }
                
                $newHoliday = $this->replicate();
                $newHoliday->holiday_date = Carbon::createFromDate($year, $config['month'], $config['day_of_month']);
                $newHoliday->source = 'recurring';
                $newHoliday->reference_code = $this->id . '_' . $year;
                $newHoliday->save();
                
                $generated[] = $newHoliday;
            }
        }
        
        return $generated;
    }

    public function getConflictingSchedules(): array
    {
        $query = EmployeeMonthlySchedule::where('effective_date', $this->holiday_date)
            ->where('status', 'active')
            ->with(['employee', 'location', 'monthlySchedule']);
        
        if ($this->location_id) {
            $query->where('location_id', $this->location_id);
        }
        
        return $query->get()->map(function($schedule) {
            return [
                'schedule_id' => $schedule->id,
                'employee_name' => $schedule->employee->full_name,
                'location_name' => $schedule->location->name,
                'schedule_name' => $schedule->monthlySchedule->name,
                'working_hours' => $schedule->working_hours,
                'start_time' => $schedule->start_time->format('H:i'),
                'end_time' => $schedule->end_time->format('H:i')
            ];
        })->toArray();
    }

    public static function getHolidaysForMonth(int $month, int $year, string $locationId = null): array
    {
        return static::active()
            ->whereMonth('holiday_date', $month)
            ->whereYear('holiday_date', $year)
            ->forLocation($locationId)
            ->orderBy('holiday_date')
            ->get()
            ->map(function($holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'date' => $holiday->holiday_date->format('Y-m-d'),
                    'formatted_date' => $holiday->formatted_date,
                    'day_name' => $holiday->day_name,
                    'type' => $holiday->type,
                    'type_label' => $holiday->type_label,
                    'scope' => $holiday->scope,
                    'is_recurring' => $holiday->is_recurring,
                    'paid_leave' => $holiday->paid_leave
                ];
            })->toArray();
    }

    public static function getUpcomingHolidays(int $days = 30, string $locationId = null): array
    {
        return static::active()
            ->upcoming($days)
            ->forLocation($locationId)
            ->get()
            ->map(function($holiday) {
                return [
                    'id' => $holiday->id,
                    'name' => $holiday->name,
                    'date' => $holiday->holiday_date->format('Y-m-d'),
                    'formatted_date' => $holiday->formatted_date,
                    'days_until' => $holiday->days_until,
                    'type_label' => $holiday->type_label,
                    'scope' => $holiday->scope
                ];
            })->toArray();
    }

    /**
     * Validation Rules
     */
    
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'holiday_date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:national,regional,religious,school,custom',
            'description' => 'nullable|string|max:1000',
            'location_id' => 'nullable|uuid|exists:locations,id',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'force_override' => 'boolean',
            'paid_leave' => 'boolean',
            'recurrence_config' => 'nullable|array',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Model Events
     */
    
    protected static function boot()
    {
        parent::boot();
        
        static::created(function ($model) {
            // Auto-apply to existing schedules if force_override is true
            if ($model->force_override) {
                $model->applyToSchedules();
            }
        });
        
        static::updated(function ($model) {
            // Re-apply if force_override changed or date changed
            if ($model->wasChanged(['force_override', 'holiday_date']) && $model->force_override) {
                $model->applyToSchedules();
            }
        });
        
        static::deleted(function ($model) {
            // Remove holiday overrides when holiday is deleted
            $model->removeFromSchedules();
        });
    }
}