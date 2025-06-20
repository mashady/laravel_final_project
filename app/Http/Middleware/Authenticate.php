<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // Return JSON 401 for API requests
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
