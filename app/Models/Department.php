<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all users belonging to this department
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all students in this department
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        });
    }

    /**
     * Get all lecturers in this department
     */
    public function lecturers(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function ($query) {
            $query->where('name', 'lecturer');
        });
    }

    /**
     * Scope to get only active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the total number of users in this department
     */
    public function getTotalUsersAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Get the total number of students in this department
     */
    public function getTotalStudentsAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Get the total number of lecturers in this department
     */
    public function getTotalLecturersAttribute(): int
    {
        return $this->lecturers()->count();
    }
}
