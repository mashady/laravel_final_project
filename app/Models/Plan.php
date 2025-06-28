<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Subscription;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'billing_interval',
        'duration',
        'ads_Limit',
        'features'
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}