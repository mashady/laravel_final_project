<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Stud;
use App\Models\OwnerProfile;
use App\Models\StudentProfile;
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

        if ($user) {
            if ($user->role === 'student') {
            $user->load('studentProfile');
            } elseif ($user->role === 'owner') {
            $user->load('ownerProfile', 'ads.media');
            }
        }

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
    /**
 * Update user and their profile data
 */
/**
 * Update user and their profile data in a single request
 */
public function updateWithProfile(Request $request, $id)
{
    // Find the user with their profile
    $user = User::with(['studentProfile', 'ownerProfile'])->find($id);

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
            'data' => null
        ], 404);
    }

    // Validate user data
    $userValidator = Validator::make($request->all(), [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,'.$user->id,
        'password' => 'sometimes|string|min:8',
        'verification_document' => 'sometimes|file|mimes:pdf,jpg,png|max:2048',
        'verification_status' => 'sometimes|in:pending,verified,rejected',
        // Profile fields that might be sent at root level
        'bio' => 'sometimes|string',
        'phone_number' => 'sometimes|string',
        'whatsapp_number' => 'sometimes|string',
        'address' => 'sometimes|string',
        // Student specific
        'university' => 'sometimes|string|required_if:role,student',
        // Owner specific
        'institution' => 'sometimes|string',
        'qualification' => 'sometimes|string',
        // Picture
        'picture' => 'sometimes|file|image|max:2048',
    ]);

    if ($userValidator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $userValidator->errors()
        ], 422);
    }

    // Start database transaction
    \DB::beginTransaction();

    try {
        // Update user basic info
        $userData = $request->only(['name', 'email', 'verification_status']);
        
        if ($request->has('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        // Handle verification document update
        if ($request->hasFile('verification_document')) {
            $document = $request->file('verification_document');
            $fileName = time() . '_' . Str::slug($user->name) . '.' . $document->getClientOriginalExtension();

            $docsDir = public_path('documents');
            if (!file_exists($docsDir)) {
                mkdir($docsDir, 0755, true);
            }

            // Delete old document if exists
            if ($user->verification_document) {
                $oldDocument = public_path('documents/' . basename($user->verification_document));
                if (file_exists($oldDocument)) {
                    unlink($oldDocument);
                }
            }

            $document->move($docsDir, $fileName);
            $userData['verification_document'] = url('documents/' . $fileName);
        }

        $user->update($userData);

        // Common profile fields
        $profileData = $request->only([
            'bio', 'phone_number', 'whatsapp_number', 'address'
        ]);

        // Handle picture upload for profile
        if ($request->hasFile('picture')) {
            $picture = $request->file('picture');
            $fileName = time() . '_' . Str::slug($user->name) . '.' . $picture->getClientOriginalExtension();
            $picturesDir = public_path('profile_pictures');
            
            if (!file_exists($picturesDir)) {
                mkdir($picturesDir, 0755, true);
            }

            // Delete old picture if exists
            $oldPicture = null;
            if ($user->isStudent() && $user->studentProfile && $user->studentProfile->picture) {
                $oldPicture = public_path('profile_pictures/' . basename($user->studentProfile->picture));
            } elseif ($user->isOwner() && $user->ownerProfile && $user->ownerProfile->picture) {
                $oldPicture = public_path('profile_pictures/' . basename($user->ownerProfile->picture));
            }

            if ($oldPicture && file_exists($oldPicture)) {
                unlink($oldPicture);
            }

            $picture->move($picturesDir, $fileName);
            $profileData['picture'] = url('profile_pictures/' . $fileName);
        }

        // Update profile based on role
        if ($user->isStudent()) {
            $profileData['university'] = $request->university;
            $profile = StudentProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        } elseif ($user->isOwner()) {
            $profileData['institution'] = $request->institution ?? null;
            $profileData['qualification'] = $request->qualification ?? null;
            $profile = OwnerProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        }

        // Commit transaction
        \DB::commit();

        // Refresh the user with profile
        $user->refresh();
        $user->load($user->isStudent() ? 'studentProfile' : 'ownerProfile');

        return response()->json([
            'success' => true,
            'message' => 'User and profile updated successfully',
            'data' => $user
        ], 200);

    } catch (\Exception $e) {
        \DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update user and profile',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
