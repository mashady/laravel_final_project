<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function toggle(Request $request, Ad $ad)
    {
        $user = Auth::user();
        
        if ($user->wishlistAds()->where('ad_id', $ad->id)->exists()) {
            $user->wishlistAds()->detach($ad);
            $action = 'removed';
        } else {
            $user->wishlistAds()->attach($ad);
            $action = 'added';
        }
        
        return response()->json([
            'success' => true,
            'message' => "Ad {$action} to wishlist",
            'is_in_wishlist' => $action === 'added'
        ]);
    }

    public function check(Ad $ad)
    {
        $isInWishlist = Auth::user()->wishlistAds()
            ->where('ad_id', $ad->id)
            ->exists();
            
        return response()->json([
            'success' => true,
            'is_in_wishlist' => $isInWishlist
        ]);
    }

    public function index()
    {
        $wishlist = Auth::user()->wishlistAds()
            ->with(['owner', 'primaryImage', 'amenities'])
            ->paginate(10);
            
        return response()->json([
            'success' => true,
            'data' => $wishlist
        ]);
    }
}