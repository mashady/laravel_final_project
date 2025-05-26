<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function ads()
    {
        return $this->belongsToMany(Ad::class, 'ad_amenities')
                    ->withPivot('notes')
                    ->withTimestamps();
    }
}
