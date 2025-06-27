<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Comment;

class CommentPolicy
{
    /**
     * Only the owner of the comment can update it.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Only the owner or admin can delete a comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        $admin= $user->role;
        return $user->id === $comment->user_id || $admin;
    }
}
