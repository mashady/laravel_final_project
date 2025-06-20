<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
class Review extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'owner_id', 'ad_id', 'content'];

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}