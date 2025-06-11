<?php

use Illuminate\Support\Facades\Broadcast;



Broadcast::routes();

Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id; // Adjust logic as needed
});

