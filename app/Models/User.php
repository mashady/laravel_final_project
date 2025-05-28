<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'verification_status',
        'verification_document',
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
public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function ownerProfile()
    {
        return $this->hasOne(OwnerProfile::class);
    }

    public function ads()
    {
        return $this->hasMany(Ad::class, 'owner_id');
    }

    public function sentReviews()
    {
        return $this->hasMany(Review::class, 'user_id'); // Reviews sent by this user (as student)
    }

    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'owner_id'); // Reviews received by this user (as owner)
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'student_id');
    }

    public function verificationRequests()
    {
        return $this->hasMany(VerificationRequest::class);
    }

    // Helper methods
    public function isVerified()
    {
        return $this->verification_status === 'verified';
    }

    public function isOwner()
    {
        return $this->role === 'owner';
    }

    public function isStudent()
    {
        return $this->role === 'student';
    }
}
