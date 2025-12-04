<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use Carbon\Carbon;

class MonthlySchedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'month',
        'year',
        'start_date',
        'end_date',
        'default_start_time',
        'default_end_time',
        'checkin_start_time',
        'checkin_end_time',
        'checkout_start_time',
        'checkout_end_time',
        'working_days',
        'total_working_days',
        'working_hours_per_day',
        'working_hours_template',
        'location_id',
        'is_active',
        'description',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'default_start_time' => 'datetime:H:i',
        'default_end_time' => 'datetime:H:i',
        'checkin_start_time' => 'datetime:H:i',
        'checkin_end_time' => 'datetime:H:i',
        'checkout_start_time' => 'datetime:H:i',
        'checkout_end_time' => 'datetime:H:i',
        'working_days' => 'array',
        'working_hours_per_day' => 'array',
        'total_working_days' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'month' => 'integer',
        'year' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'metadata' => '{}',
    ];

    /**
     * Scope to filter active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter schedules by month and year
     */
    public function scopeForMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    /**
     * Scope to filter current schedules
     */
    public function scopeCurrent($query)
    {
        $now = Carbon::now();
        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    /**
     * Relationships
     */

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeMonthlySchedule::class);
    }

    public function employeeMonthlySchedules(): HasMany
    {
        return $this->hasMany(EmployeeMonthlySchedule::class);
    }

    public function teachingSchedules(): HasMany
    {
        return $this->hasMany(TeachingSchedule::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function scopeForLocation($query, string $locationId)
    {
        return $query->where('location_id', $locationId);
    }


    /**
     * Accessors & Mutators
     */

    public function getMonthNameAttribute(): string
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->format('F');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} - {$this->month_name} {$this->year}";
    }

    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getWorkingHoursAttribute(): float
    {
        $start = Carbon::createFromFormat('H:i', $this->default_start_time->format('H:i'));
        $end = Carbon::createFromFormat('H:i', $this->default_end_time->format('H:i'));

        return $start->diffInHours($end);
    }

    /**
     * Business Logic Methods
     */

    public function generateDailySchedules(): int
    {
        $generated = 0;
        $current = $this->start_date->copy();

        while ($current->lte($this->end_date)) {
            // Only generate for weekdays by default (can be overridden in metadata)
            $workDays = $this->metadata['work_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

            if (in_array(strtolower($current->format('l')), $workDays)) {
                // We'll generate employee schedules when employees are assigned
                // This method is for validation and preparation
                $generated++;
            }

            $current->addDay();
        }

        return $generated;
    }

    public function assignEmployee(Employee $employee): bool
    {
        $assignments = 0;

        // Ensure dates are Carbon instances
        if (!$this->start_date || !$this->end_date) {
            \Log::error('MonthlySchedule missing dates', [
                'schedule_id' => $this->id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date
            ]);
            return false;
        }

        $startDate = $this->start_date instanceof Carbon ? $this->start_date : Carbon::parse($this->start_date);
        $endDate = $this->end_date instanceof Carbon ? $this->end_date : Carbon::parse($this->end_date);
        $current = $startDate->copy();

        // Use working_days array if available (this is the source of truth for this schedule)
        // working_days contains array of date strings (Y-m-d)
        $workingDaysList = $this->working_days ?? [];
        $hasWorkingDaysList = !empty($workingDaysList) && is_array($workingDaysList);

        \Log::info("Starting assignment loop. Start: {$startDate->toDateString()}, End: {$endDate->toDateString()}");
        \Log::info("Has working days list: " . ($hasWorkingDaysList ? 'Yes' : 'No'));
        if ($hasWorkingDaysList) {
            \Log::info("Working days count: " . count($workingDaysList));
            \Log::info("First few working days: " . implode(', ', array_slice($workingDaysList, 0, 3)));
        }

        while ($current->lte($endDate)) {
            $isWorkingDay = false;
            $currentDateStr = $current->format('Y-m-d');

            if ($hasWorkingDaysList) {
                // Check if current date is in working_days array
                if (in_array($currentDateStr, $workingDaysList)) {
                    $isWorkingDay = true;
                }
            } else {
                // Fallback to metadata pattern
                $workDays = $this->metadata['work_days'] ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
                if (in_array(strtolower($current->format('l')), $workDays)) {
                    $isWorkingDay = true;
                }
            }

            // \Log::info("Checking date: {$currentDateStr}, Is Working Day: " . ($isWorkingDay ? 'Yes' : 'No'));

            if ($isWorkingDay) {
                // Calculate scheduled hours from start and end time
                $startTime = Carbon::parse($this->default_start_time);
                $endTime = Carbon::parse($this->default_end_time);
                // Ensure positive duration
                $scheduledHours = $startTime->diffInHours($endTime, true);

                try {
                    // Manually handle find-restore-update to ensure reliability with soft deletes and unique constraints
                    $existingSchedule = EmployeeMonthlySchedule::withTrashed()
                        ->where('employee_id', $employee->id)
                        ->where('effective_date', 'like', $current->toDateString() . '%')
                        ->first();

                    if ($existingSchedule) {
                        // If it exists (active or soft-deleted), update it
                        if ($existingSchedule->trashed()) {
                            $existingSchedule->restore();
                        }

                        $existingSchedule->update([
                            'monthly_schedule_id' => $this->id,
                            'start_time' => $this->default_start_time,
                            'end_time' => $this->default_end_time,
                            'location_id' => $this->location_id,
                            'scheduled_hours' => $scheduledHours,
                            'is_weekend' => $current->isWeekend(),
                            'assigned_by' => auth()->id(),
                            'status' => 'active', // Reset status to active
                            'override_metadata' => [], // Reset overrides
                        ]);
                    } else {
                        // If it doesn't exist, create it
                        EmployeeMonthlySchedule::create([
                            'employee_id' => $employee->id,
                            'effective_date' => $current->toDateString(),
                            'monthly_schedule_id' => $this->id,
                            'start_time' => $this->default_start_time,
                            'end_time' => $this->default_end_time,
                            'location_id' => $this->location_id,
                            'scheduled_hours' => $scheduledHours,
                            'is_weekend' => $current->isWeekend(),
                            'assigned_by' => auth()->id(),
                        ]);
                    }

                    $assignments++;
                } catch (\Exception $e) {
                    \Log::error('Failed to create/update employee schedule', [
                        'employee_id' => $employee->id,
                        'schedule_id' => $this->id,
                        'date' => $current->toDateString(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $current->addDay();
        }

        return $assignments > 0;
    }

    public function bulkAssignEmployees(array $employeeIds): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($employeeIds as $employeeId) {
            try {
                $employee = Employee::findOrFail($employeeId);

                if ($this->assignEmployee($employee)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "No new schedules created for {$employee->full_name}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Failed to assign employee {$employeeId}: " . $e->getMessage();
            }
        }

        return $results;
    }

    public function getAssignedEmployeesCount(): int
    {
        return $this->employeeSchedules()
            ->distinct()
            ->count('employee_id');
    }

    public function getHolidayConflicts(): array
    {
        $holidays = NationalHoliday::whereBetween('holiday_date', [$this->start_date, $this->end_date])
            ->where(function ($query) {
                $query->whereNull('location_id')
                    ->orWhere('location_id', $this->location_id);
            })
            ->where('is_active', true)
            ->get();

        return $holidays->map(function ($holiday) {
            return [
                'date' => $holiday->holiday_date,
                'name' => $holiday->name,
                'type' => $holiday->type,
                'affected_schedules' => $this->employeeSchedules()
                    ->where('effective_date', $holiday->holiday_date)
                    ->count()
            ];
        })->toArray();
    }

    public function applyHolidayOverrides(): int
    {
        $overridden = 0;
        $holidays = NationalHoliday::whereBetween('holiday_date', [$this->start_date, $this->end_date])
            ->where(function ($query) {
                $query->whereNull('location_id')
                    ->orWhere('location_id', $this->location_id);
            })
            ->where('is_active', true)
            ->get();

        foreach ($holidays as $holiday) {
            $affected = $this->employeeSchedules()
                ->where('effective_date', $holiday->holiday_date)
                ->where('status', 'active')
                ->update([
                    'status' => 'holiday',
                    'is_holiday' => true,
                    'override_metadata' => json_encode([
                        'override_type' => 'holiday',
                        'holiday_name' => $holiday->name,
                        'holiday_type' => $holiday->type,
                        'override_at' => now(),
                        'override_by' => auth()->id()
                    ])
                ]);

            $overridden += $affected;
        }

        return $overridden;
    }

    /**
     * Validation Rules
     */

    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024|max:2030',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'location_id' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'working_hours_template' => 'nullable|string|in:standard_5_days,uniform_5_days,half_day_saturday,custom',
            'working_hours_per_day' => 'required|array',
            'metadata' => 'nullable|array',
        ];
    }
}