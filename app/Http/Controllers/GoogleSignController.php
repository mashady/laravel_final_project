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

            
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                
                $user->update([
                    'name' => $googleUser->getName(),
                    'social_id' => $googleUser->getId(),
                    'social_type' => 'google',
                    'email_verified_at' => now(),
                ]);
            } else {
                
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'social_id' => $googleUser->getId(),
                    'social_type' => 'google',
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                    'verification_status' => 'pending',
                ]);
            }

            
            if (!$user->role || !$user->verification_document) {
                return redirect()->away("http://localhost:3000/google?user_id={$user->id}");
            }

            if ($user->verification_status !== 'verified') {
                return redirect()->away("http://localhost:3000/google/callback?user_id={$user->id}&status={$user->verification_status}");
            }
            
            $token = $user->createToken('API Token')->plainTextToken;

            return redirect()->away("http://localhost:3000/google/callback?token=$token&user_id={$user->id}&status={$user->verification_status}");

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function completeProfile(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:student,owner',
            'verification_document' => 'required|file|mimes:pdf,png,jpg,jpeg|max:5120',
            'profile_image' => ['nullable']
        ]);

        $user = User::find($request->user_id);

        if ($request->hasFile('verification_document')) {

            $document = $request->file('verification_document');
            $fileName = time() . '_' . Str::slug($request->name) . '.' . $document->getClientOriginalExtension();

            $docsDir = public_path('documents');
            if( !file_exists($docsDir) ) {
                mkdir($docsDir, 0755, true);
            }

            $document->move($docsDir, $fileName);

            $documentPath = url('documents/' . $fileName);


            // $path = $request->file('verification_document')->store('documents', 'public');
            $user->verification_document = $documentPath;
        }

        if ($request->hasFile('profile_image')) {

            $image = $request->file('profile_image');
            $fileName = time() . '_' . Str::slug($request->name) . '.' . $image->getClientOriginalExtension();

            $imageDir = public_path('profile_pictures');
            if( !file_exists($imageDir) ) {
                mkdir($imageDir, 0755, true);
            }

            $image->move($imageDir, $fileName);

            $imagePath = url('profile_pictures/' . $fileName);

            $user_profile_image = $imagePath;
        }

        $user->role = $request->role;
        if( !request()->has('verification_status') ) {
            $user->verification_status = 'pending';
        } else {
            $user->verification_status = $request->verification_status;
        }
        $user->save();

        // Create profile based on role if not exists
        if ($user->role === 'student' && !$user->studentProfile) {
            $user->studentProfile()->create([
                'picture' => $user_profile_image,
                'bio' => null,
                'university' => null,
                'phone_number' => null,
                'whatsapp_number' => null,
                'address' => null,
            ]);
        } elseif ($user->role === 'owner' && !$user->ownerProfile) {
            $user->ownerProfile()->create([
                'picture' => $user_profile_image,
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
