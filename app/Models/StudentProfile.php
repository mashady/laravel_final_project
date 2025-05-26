<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'picture', 'bio', 'university'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
