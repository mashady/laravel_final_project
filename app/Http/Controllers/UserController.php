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
            'success' => true,
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
            'success' => true,
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
        $user = User::find($id);

        if( !$user ) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'data' => null
            ], 404);
        }

        return response()->json([
                'success' => true,
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
                'success' => false,
                'message' => 'User not found.',
                'data' => null
            ], 404);
        }

        // Get all validated data from the request
        $validatedData = $request->validated();

        // Handle file upload first if present
        if ($request->hasFile('verification_document')) {
            $document = $request->file('verification_document');
            
            // Always use the user's name (updated name if provided, otherwise current name)
            $userName = $request->filled('name') ? $request->input('name') : $user->name;
            $fileName = time() . '_' . Str::slug($userName) . '.' . $document->getClientOriginalExtension();

            // Create documents directory in public folder
            $docsDir = public_path('documents');
            if (!file_exists($docsDir)) {
                mkdir($docsDir, 0755, true);
            }
            
            // Move the uploaded file
            $document->move($docsDir, $fileName);
            $documentPath = url('documents/' . $fileName);
            
            // Delete old document if it exists
            if ($user->verification_document) {
                $oldDocumentName = basename(parse_url($user->verification_document, PHP_URL_PATH));
                $oldFilePath = public_path('documents/' . $oldDocumentName);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            // Update the user's verification document path
            $user->verification_document = $documentPath;
        }

        // Remove verification_document and _method from validated data
        unset($validatedData['verification_document']);
        unset($validatedData['_method']); // Remove method spoofing field
        
        // Update all other fields that are present in the request
        foreach ($validatedData as $key => $value) {
            if ($request->has($key) && $key !== '_method') { // Skip _method field
                if ($key === 'password') {
                    // Hash password before saving
                    $user->password = Hash::make($value);
                } else {
                    $user->$key = $value;
                }
            }
        }

        // Save all changes
        $user->save();

        return response()->json([
            'success' => true,
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
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'data' => null
            ], 404);
        }

        $user->delete();

        return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
                'data' => null
            ], 200);
    }
}
