<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;


class User extends Authenticatable implements MustVerifyEmail
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
        'social_id',
        'social_type',
        'email_verified_at',
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
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function wishlistAds()
{
    return $this->belongsToMany(Ad::class, 'wishlists', 'user_id', 'ad_id')
        ->withTimestamps();
}
    public function subscription()
    {
        return $this->hasOne(Subscription::class)
                    ->where('active', true)
                    ->orderByDesc('id'); // or created_at
    }
    
    

    public function hasActiveSubscription()
    {
        return $this->subscription && $this->subscription->active && $this->subscription->ends_at > now();
    }

    public function isSubscribedToPlan($planId)
    {
        return $this->subscription && $this->subscription->plan_id === $planId;
    }

}
