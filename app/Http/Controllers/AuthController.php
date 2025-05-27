<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function register(StoreUserRequest $request)
    {
        //
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
            
            // Store the path for database
            $documentPath = url('documents/', $filename);

            //Update the document_path in request
            $request['verification_document'] = $documentPath;
        }

        //Hash the password before storing in DB
        $request->password = Hash::make($request['password']);
    
        $user = User::create($request->all());

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $user,
            'token' => $token
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function login(Request $request)
    {
        //
    }
}
