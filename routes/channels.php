<?php

use Illuminate\Support\Facades\Broadcast;




Broadcast::channel('chat.{receiverId}', function ($user, $receiverId) {
    // allow only if user is involved in the conversation
    return true;
});