<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'username',
        'role',
        'division_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(User::class, 'division_id');
    }

    public function ranges(): HasMany
    {
        return $this->hasMany(User::class, 'division_id')->where('role', 'range');
    }

    public function isDivision(): bool
    {
        return $this->role === 'division';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isRange(): bool
    {
        return $this->role === 'range';
    }

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
}
