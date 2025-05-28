<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Resources\ReviewResource;

class ReviewController extends Controller
{
    
    public function store(StoreReviewRequest $request)
    {
        $review = Review::create([
            'user_id' => $request->user()->id,
            'owner_id' => $request->owner_id,
            'content' => $request->content,

            // Optional: If you have a rating field
        
        ]);

        return response()->json([
            'message' => 'the review has been created successfully',
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
}
