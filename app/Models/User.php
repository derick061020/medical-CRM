<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    public function canAccessFilament(): bool
    {
        return $this->is_active;
    }
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'clinic_id',
        'type',
        'specialty_id',
        'doc_info'
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
            'is_active' => 'boolean',
            'doc_info' => 'array'
        ];
    }

    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'user_specialties');
    }

    /**
     * Get the laboratory results for the user.
     */
    public function laboratoryResults()
    {
        return $this->hasMany(LaboratoryResult::class, 'assigned_doctor_id')
            ->where('status', 'pending');
    }

    /**
     * Get the citas for the user.
     */
    public function citas()
    {
        return $this->hasMany(Cita::class);
    }
}
