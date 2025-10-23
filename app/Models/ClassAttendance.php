<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ClassAttendance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'class_id',
        'student_id',
        'full_name',
        'matric_number',
        'latitude',
        'longitude',
        'distance',
        'marked_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'distance' => 'decimal:2',
        'marked_at' => 'datetime',
    ];

    /**
     * Get the class this attendance belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the student who marked this attendance.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get formatted coordinates.
     */
    public function getFormattedCoordinatesAttribute(): string
    {
        return "{$this->latitude}, {$this->longitude}";
    }

    /**
     * Get formatted distance.
     */
    public function getFormattedDistanceAttribute(): string
    {
        return number_format($this->distance, 1) . 'm';
    }

    /**
     * Check if attendance was marked within the allowed radius.
     */
    public function isWithinAllowedRadius(): bool
    {
        return $this->distance <= $this->class->radius;
    }

    /**
     * Create attendance record with location validation.
     */
    public static function markAttendance(
        ClassModel $class,
        User $student,
        string $fullName,
        string $matricNumber,
        float $latitude,
        float $longitude
    ): self {
        // Calculate distance from class location
        $distance = ClassModel::calculateDistance(
            $class->latitude,
            $class->longitude,
            $latitude,
            $longitude
        );

        // Check if within allowed radius
        if ($distance > $class->radius) {
            throw new \Exception("You are too far from the class location. Distance: " . number_format($distance, 1) . "m, Required: {$class->radius}m");
        }

        // Check if student already marked attendance for this class
        $existingAttendance = self::where('class_id', $class->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingAttendance) {
            throw new \Exception("You have already marked attendance for this class.");
        }

        // Create attendance record
        return self::create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'full_name' => $fullName,
            'matric_number' => $matricNumber,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance' => $distance,
            'marked_at' => now(),
        ]);
    }

    /**
     * Scope to get attendance for a specific class.
     */
    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope to get attendance for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to order by attendance time.
     */
    public function scopeOrderByMarkedAt($query, $direction = 'asc')
    {
        return $query->orderBy('marked_at', $direction);
    }
}
