<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    protected $fillable = ['user_id', 'question', 'answer', 'sources'];

    protected $casts = [
        'sources' => 'array',
    ];
}
