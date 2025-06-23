<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class GoogleSignController extends Controller
{
    public function redirectToGoogle()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $url]);
    }


    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'social_id' => $googleUser->getId(),
                    'social_type' => 'google',
                    'password' => bcrypt(Str::random(16)), // random password
                    'email_verified_at' => now(),
                ]
            );

            // Check if role or document missing:
            if (!$user->role || !$user->verification_document) {
                return redirect()->away("http://localhost:3000/google?user_id={$user->id}");
            }

            // Else generate token (Sanctum or Passport):
            $token = $user->createToken('API Token')->plainTextToken;

            return redirect()->away("http://localhost:3000/google/callback?token=$token&user_id={$user->id}");

        } catch (\Exception $e) {
            return response()->json(['error' => 'Google authentication failed'], 500);
        }
    }

    public function completeProfile(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:student,owner',
            'verification_document' => 'required|file|mimes:pdf,png,jpg,jpeg|max:5120', // 5MB
        ]);

        $user = User::find($request->user_id);

        if ($request->hasFile('verification_document')) {
            $path = $request->file('verification_document')->store('documents', 'public');
            $user->verification_document = $path;
        }

        $user->role = $request->role;
        $user->verification_status = 'pending';
        $user->save();

        // Create profile based on role if not exists
        if ($user->role === 'student' && !$user->studentProfile) {
            $user->studentProfile()->create([
                'picture' => null,
                'bio' => null,
                'university' => null,
                'phone_number' => null,
                'whatsapp_number' => null,
                'address' => null,
            ]);
        } elseif ($user->role === 'owner' && !$user->ownerProfile) {
            $user->ownerProfile()->create([
                'picture' => null,
                'bio' => null,
                'phone_number' => null,
                'whatsapp_number' => null,
                'address' => null,
                'institution' => null,
                'qualification' => null,
            ]);
        }

        // Generate token for the user
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => 'Profile completed',
            'token' => $token,
            'user' => $user->load($user->role === 'student' ? 'studentProfile' : 'ownerProfile'),
        ]);
    }

}
