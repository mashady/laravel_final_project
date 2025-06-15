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
        $documentPath = url('documents/' . $filename);
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $user->load($user->role === 'student' ? 'studentProfile' : 'ownerProfile'),
            'token' => $token
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
