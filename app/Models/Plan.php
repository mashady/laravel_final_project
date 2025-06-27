<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Subscription;

class Plan extends Model
{
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