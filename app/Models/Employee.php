<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee Model
 *
 * Optimized for performance with proper caching and eager loading
 */
class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'employee_id', 'full_name', 'phone',
        'photo_path', 'employee_type', 'hire_date', 'salary_type',
        'salary_amount', 'hourly_rate', 'location_id', 'metadata', 'is_active',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'salary_amount' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $with = ['user']; // Always eager load user

    // ========== RELATIONSHIPS ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithTodayAttendance($query)
    {
        return $query->with(['attendances' => fn ($q) => $q->whereDate('created_at', today())]);
    }

    public function scopeWithLatestAttendance($query)
    {
        return $query->with(['attendances' => fn ($q) => $q->latest()->limit(1)]);
    }

    // ========== ACCESSORS ==========

    public function getNameAttribute(): string
    {
        return $this->full_name ?? '';
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo_path
            ? asset("storage/{$this->photo_path}")
            : 'https://ui-avatars.com/api/?name='.urlencode($this->full_name);
    }

    public function getFaceRegisteredAttribute(): bool
    {
        return isset($this->metadata['face_recognition']['descriptor']);
    }

    // ========== OPTIMIZED QUERIES ==========

    /**
     * Get employees with minimal data for lists
     */
    public static function minimal()
    {
        return static::select(['id', 'first_name', 'last_name', 'employee_id', 'is_active'])
            ->with(['user:id,name,email'])
            ->orderBy('first_name');
    }

    /**
     * Get employees for dashboard stats
     */
    public static function forStats()
    {
        return static::select(['id', 'is_active', 'created_at'])
            ->withCount(['attendances', 'leaves']);
    }

    /**
     * Get employees for DataTables
     */
    public static function forDataTable()
    {
        return static::select([
            'id', 'employee_id', 'first_name', 'last_name',
            'employee_type', 'is_active', 'user_id', 'location_id',
        ])->with([
            'user:id,name,email',
            'location:id,name',
            'user.roles:id,name',
        ]);
    }

    // ========== BUSINESS METHODS ==========

    public function isActiveToday(): bool
    {
        return $this->attendances()
            ->whereDate('created_at', today())
            ->exists();
    }

    public function hasCheckedInToday(): bool
    {
        return $this->attendances()
            ->whereDate('created_at', today())
            ->whereNotNull('check_in_time')
            ->exists();
    }

    public function getTodayAttendance()
    {
        return $this->attendances()
            ->whereDate('created_at', today())
            ->first();
    }
}
