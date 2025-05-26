<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'ad_id',
        'file_path',
        'media_type',
        'is_primary',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }
}
