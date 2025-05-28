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

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $users = User::all();

        return response()->json([
            'success' => 'true',
            'message' => 'Users retrieved successfully',
            'data' => $users
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        //
        $documentPath = null;

        if( $request->hasFile('verification_document') ) {

            $document = $request->file('verification_document');
            $fileName = time() . '_' . Str::slug($request->name) . '.' . $document->getClientOriginalExtension();

            $docsDir = public_path('documents');
            if( !file_exists($docsDir) ) {
                mkdir($docsDir, 0755, true);
            }

            $document->move($docsDir, $fileName);

            $documentPath = url('documents/' . $fileName);
        }

        $data = $request->only(['name', 'email', 'role', 'verification_status']);
        $data['password'] = Hash::make($request->password);
        $data['verification_document'] = $documentPath;

        $user = User::create($data);

        return response()->json([
            'success' => 'true',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $user = User->find($id);

        if( !$user ) {
            return response()->json([
                'success' => 'false',
                'message' => 'User not found.',
                'data' => null
            ], 404);
        }

        return response()->json([
                'success' => 'true',
                'message' => 'User retrieved successfully.',
                'data' => $user
            ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => 'false',
                'message' => 'User not found.',
                'data' => null
            ], 404);
        }

        $documentPath = null;

        if ($request->hasFile('verification_document')) {
            $document = $request->file('verification_document');
            $fileName = time() . '_' . Str::slug($request->input('name', 'user')) . '.' . $document->getClientOriginalExtension();

            $docsDir = public_path('documents');
            if (!file_exists($docsDir)) {
                mkdir($docsDir, 0755, true);
            }

            $document->move($docsDir, $fileName);
            $documentPath = url('documents/' . $fileName);
        }

        // Only update if the field is present
        if ($request->filled('name')) {
            $user->name = $request->input('name');
        }
        if ($request->filled('email')) {
            $user->email = $request->input('email');
        }
        if ($request->filled('role')) {
            $user->role = $request->input('role');
        }
        if ($request->filled('verification_status')) {
            $user->verification_status = $request->input('verification_status');
        }
        if ($documentPath) {
            $user->verification_document = $documentPath;
        }

        $user->save();

        return response()->json([
            'success' => 'true',
            'message' => 'User updated successfully.',
            'data' => $user
        ], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
