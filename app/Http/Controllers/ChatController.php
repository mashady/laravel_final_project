<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Message;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function getMessages(Request $request, User $user)
    {
        $authUser = $request->user();
        $messages = Message::where(function ($q) use ($user, $authUser) {
            $q->where('sender_id', $authUser->id)->where('receiver_id', $user->id);
        })->orWhere(function ($q) use ($user, $authUser) {
            $q->where('sender_id', $user->id)->where('receiver_id', $authUser->id);
        })->orderBy('created_at')->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $authUser = $request->user();
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => $authUser->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        // (Optional) broadcast event here

        return response()->json($message);
    }
    public function inbox(Request $request)
{
    $authUser = $request->user();
    $messages = Message::where('receiver_id', $authUser->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($messages);
}
}
