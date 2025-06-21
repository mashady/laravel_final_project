<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['title', 'content'];

    // app/Models/Document.php

    public function scopeSearchByContent($query, $text)
    {
        return $query->whereRaw(
            "to_tsvector('english', content) @@ plainto_tsquery('english', ?)",
            [$text]
        );
    }

}
