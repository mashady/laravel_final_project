<?php

// app/Services/MapboxService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class MapboxService
{
    public static function geocodeAddress(string $query): ?array
    {
        $token = config('services.mapbox.token');
        $response = Http::get("https://api.mapbox.com/geocoding/v5/mapbox.places/{$query}.json", [
            'access_token' => $token,
            'limit' => 1,
            'country' => 'EG',
        ]);
        
        
        if ($response->successful() && !empty($response->json()['features'])) {
            $feature = $response->json()['features'][0];
            return [
                'longitude' => $feature['center'][0],
                'latitude' => $feature['center'][1],
                'location' => $feature['place_name']
            ];
        }
        

        return null;
    }
}