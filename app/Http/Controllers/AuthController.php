<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    
    public function register(StoreUserRequest $request)
    {
        $documentPath = null;
        
        if ($request->hasFile('verification_document')) {
            $image = $request->file('verification_document');
            $filename = time() . '_' . Str::slug($request['name']) . '.' . $image->getClientOriginalExtension();
            
            // Check if directory exists, if not create it
            $docsDir = public_path('documents');
            if (!file_exists($docsDir)) {
                mkdir($docsDir, 0755, true);
            }
            
            // Move the uploaded file
            $image->move($docsDir, $filename);
            
            // Create the full path for database
            $documentPath = 'documents/' . $filename;
        }

        $data = $request->only(['name', 'email', 'role', 'verification_status']);
        $data['password'] = Hash::make($request->password);
        $data['verification_document'] = $documentPath;

        \DB::beginTransaction();

        try {
            $user = User::create($data);

            


            switch ($user->role) {
                case 'student':
                    $user->studentProfile()->create([
                        'picture' => null,
                        'bio' => null,
                        'university' => null,
                        'phone_number' => null,
                        'whatsapp_number' => null,
                        'address' => null
                    ]);
                    break;
                    
                case 'owner':
                    $user->ownerProfile()->create([
                        'picture' => null,
                        'bio' => null,
                        'phone_number' => null,
                        'whatsapp_number' => null,
                        'address' => null,
                        'institution' => null,
                        'qualification' => null
                    ]);
                    break;
                    
            }

            \DB::commit();

            // Verify Email
            $user->sendEmailVerificationNotification();

            // Notify verify status is pending
            $user->notify(new \App\Notifications\VerificationStatusChanged('pending'));

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => $user->load($user->role === 'student' ? 'studentProfile' : 'ownerProfile'),
            ], 201);

        } catch (\Exception $e) {
            // Rollback transaction on error
            \DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);


         // Check if the user is already verified
        if ($user->hasVerifiedEmail()) {
            return redirect('http://localhost:3000/verify/already-verified');
        }


        // Check if the link has a valid signature
        if (! $request->hasValidSignature()) {
            return redirect('http://localhost:3000/verify/failed');
        }

        // Mark the user's email as verified
        $user->markEmailAsVerified();

        return redirect('http://localhost:3000/verify/success');
    }

    public function resendVerificationEmailGuest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check existence
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Check if the user has already verified their email
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.'
            ], 400);
        }

        try {
            $user->sendEmailVerificationNotification();
            return response()->json([
                'success' => true,
                'message' => 'Verification link resent.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function login(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => 'false',
                'message' => 'Invalid credentials.'
            ], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email before logging in.'
            ], 403);
        }

        // Check if the user is banned
        if ($user->verification_status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your verification is being reviewed, wait for the admin to approve it.'
            ], 403);
        }   

        if ($user->verification_status === 'unverified') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been banned.'
            ], 403);
        }
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
