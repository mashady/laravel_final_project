<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    
    
    public function index(Ad $ad) {
        return $ad->comments()->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->latest()->get();
    }
    
    public function store(Request $request, Ad $property)
    {
        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);
    
        return $property->comments()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);
    }
    
    
    
    public function update(Request $request, Comment $comment) {

        $user = Auth::user();
        if( $user->id == $comment->user_id) {
            $request->validate(['content' => 'required|string']);
            $comment->update(['content' => $request->content]);
            return response()->json(['message' => 'Comment updated']);
        }

    }
    
    public function destroy(Comment $comment) {
        $user = Auth::user();
        if( $user->id != $comment->user_id  && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
    
}
