<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'matric_no',
        'avatar',
        'department_id',
        'level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Generate a random 3D avatar URL
     */
    public static function generateRandomAvatar(): string
    {
        $styles = ['adventurer', 'avataaars', 'big-ears', 'big-smile', 'croodles', 'fun-emoji', 'icons', 'identicon', 'initials', 'lorelei', 'micah', 'miniavs', 'open-peeps', 'personas', 'pixel-art', 'shapes', 'thumbs'];
        $style = $styles[array_rand($styles)];
        $seed = Str::random(10);
        
        return "https://api.dicebear.com/7.x/{$style}/svg?seed={$seed}&size=200";
    }

    /**
     * Get the user's avatar URL
     */
    public function getAvatarUrl(): string
    {
        return $this->avatar ?? $this->generateRandomAvatar();
    }

    /**
     * Get the department that the user belongs to
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user's level as a formatted string
     */
    public function getLevelDisplayAttribute(): string
    {
        return $this->level ? $this->level . ' Level' : 'No Level';
    }

    /**
     * Check if user is a student
     */
    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    /**
     * Check if user is a lecturer
     */
    public function isLecturer(): bool
    {
        return $this->hasRole('lecturer');
    }

    /**
     * Check if user can be promoted to next level
     */
    public function canBePromoted(): bool
    {
        return $this->isStudent() && $this->level && $this->level < 600;
    }

    /**
     * Get the next level for promotion
     */
    public function getNextLevel(): ?int
    {
        if (!$this->canBePromoted()) {
            return null;
        }

        return $this->level + 100;
    }

    /**
     * Promote user to next level
     */
    public function promoteToNextLevel(): bool
    {
        if (!$this->canBePromoted()) {
            return false;
        }

        $this->level = $this->getNextLevel();
        return $this->save();
    }

    /**
     * Get classes created by this lecturer
     */
    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'lecturer_id');
    }

    /**
     * Get classes attended by this student
     */
    public function attendedClasses()
    {
        return $this->belongsToMany(ClassModel::class, 'class_attendances', 'user_id', 'class_id')
                    ->withTimestamps();
    }

    /**
     * Get complaints submitted by this user
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'student_id');
    }
}
