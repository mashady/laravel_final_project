<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\MapboxService;

class Ad extends Model
{
    use HasFactory , SoftDeletes;
    protected $fillable = [
        'owner_id', 'title', 'type', /* 'picture', 'video', */
        'description', 'price', /* 'location', */ 'space',
        'number_of_beds',
        'number_of_bathrooms',
        'area',
        'street',
        'block',
        'latitude','longitude'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($property) {
            if ($property->isDirty(['area', 'street', 'block'])) {
                $address = implode(', ', array_filter([
                    $property->block,
                    $property->street,
                    $property->area
                ]));
                
                if (!empty($address)) {
                    $coordinates = MapboxService::geocodeAddress($address);
                    if ($coordinates) {
                        $property->latitude = $coordinates['latitude'];
                        $property->longitude = $coordinates['longitude'];
                    }
                }
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'ad_amenities')
                    ->withPivot('notes')
                    ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(Media::class)->where('is_primary', true);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'ad_id');
    }
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists')
            ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'ad_id');
    }
    
}