<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Builder;
class Document extends Model
{
    protected $fillable = ['title', 'content'];

    // app/Models/Document.php

    public static function searchByContent(string $text): Builder
    {
        // Extract keywords from the question
        $keywords = preg_split('/\s+/', strtolower($text));
        $query = self::query();
    
        foreach ($keywords as $word) {
            if (strlen($word) >= 3) { // ignore short/common words
                $query->orWhere('title', 'ILIKE', "%{$word}%")
                      ->orWhere('content', 'ILIKE', "%{$word}%");
            }
        }
    
        return $query;
    }

}
