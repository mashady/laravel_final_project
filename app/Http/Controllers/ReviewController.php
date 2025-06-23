<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Ad;
use Illuminate\Http\Request;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;

class ReviewController extends Controller
{
    

    // Store review for an owner (not ad-specific)
    public function storeForOwner(Request $request)
    {
        $request->validate([
            'owner_id' => 'required|exists:users,id',
            'content' => 'required|string|max:1000',
        ]);

        $review = Review::create([
            'user_id' => $request->user()->id,
            'owner_id' => $request->owner_id,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'The review has been created successfully',
            'review' => new ReviewResource($review),
        ], 201);
    }



    public function forOwner($ownerId)
    {
        $reviews = Review::with('user')
            ->where('owner_id', $ownerId)
            ->latest()
            ->get();

        return ReviewResource::collection($reviews);
    }

    public function forAd($adId)
    {
        $reviews = Review::with(['user', 'owner', 'ad'])
            ->where('ad_id', $adId)
            ->latest()
            ->get();

        return ReviewResource::collection($reviews);
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not authorized to edit this review'], 403);
        }
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);
        $review->content = $request->content;
        $review->save();
        return response()->json([
            'message' => 'Review updated successfully',
            'review' => new ReviewResource($review),
        ]);
    }

    public function destroy($id)
    {
        $review = Review::find($id);
        if (!$review) {
            return response()->json([
                'message' => 'Review not found',
            ], 404);
        }
        $request = app('request');
        if ($review->user_id !== ($request->user() ? $request->user()->id : null)) {
            return response()->json([
                'message' => 'Not authorized to delete this review',
            ], 403);
        }
        $review->delete();
        return response()->json([
            'message' => 'Deleted successfully',
        ], 200);
    }

}