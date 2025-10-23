<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ClassModel extends Model
{
    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'lecturer_id',
        'department_id',
        'level',
        'latitude',
        'longitude',
        'radius',
        'status',
        'starts_at',
        'ends_at',
        'attendance_open',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'attendance_open' => 'boolean',
    ];

    /**
     * Get the lecturer who created this class.
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * Get the department this class belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all attendances for this class.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(ClassAttendance::class, 'class_id');
    }

    /**
     * Get formatted coordinates.
     */
    public function getFormattedCoordinatesAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    /**
     * Check if the class is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->starts_at <= now() && 
               ($this->ends_at === null || $this->ends_at >= now());
    }

    /**
     * Check if attendance is open for this class.
     */
    public function isAttendanceOpen(): bool
    {
        return $this->attendance_open && $this->isActive();
    }

    /**
     * Get the total number of students who attended this class.
     */
    public function getTotalAttendeesAttribute(): int
    {
        return $this->attendances()->count();
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if a location is within the class radius.
     */
    public function isWithinRadius($latitude, $longitude): bool
    {
        $distance = self::calculateDistance(
            $this->latitude,
            $this->longitude,
            $latitude,
            $longitude
        );

        return $distance <= $this->radius;
    }

    /**
     * Scope to get active classes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('starts_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('ends_at')
                          ->orWhere('ends_at', '>=', now());
                    });
    }

    /**
     * Scope to get classes for a specific department and level.
     */
    public function scopeForDepartmentAndLevel($query, $departmentId, $level)
    {
        return $query->where('department_id', $departmentId)
                    ->where('level', $level);
    }
}
