<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Http\Requests\StudentProfileRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentProfileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profiles = StudentProfile::with('user')->get();
        if ($profiles->isEmpty()) {
            return response()->json(['message' => 'No student profiles found'], 404);
        }
        return response()->json($profiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentProfileRequest $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validated();

        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('profile-pictures', 'public');
            $validated['picture'] = $path;
        }

        $validated['user_id'] = $user->id;

        if ($user->role !== 'student') {
            return response()->json(['message' => 'User is not a student'], 400);
        }

        $existingProfile = StudentProfile::where('user_id', $user->id)->first();
        if ($existingProfile) {
            return response()->json(['message' => 'User already has a profile'], 400);
        }

        $profile = StudentProfile::create($validated);

        return response()->json([
            'message' => 'Student profile created successfully',
            'data' => $profile
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $profile = StudentProfile::where('user_id', $id)->with('user')->first();
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }
        return response()->json([
            'message' => 'Profile retrieved successfully',
            'data' => $profile
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentProfileRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $profile = StudentProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        if ($user->role !== 'student' || $profile->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->authorize('update', $profile);

        $validated = $request->validated();

        if ($request->hasFile('picture')) {
            if ($profile->picture && Storage::disk('public')->exists($profile->picture)) {
                Storage::disk('public')->delete($profile->picture);
            }

            $path = $request->file('picture')->store('profile-pictures', 'public');
            $validated['picture'] = $path;
        }

        $profile->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $profile
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $studentProfile = StudentProfile::where('user_id', $id)->first();
        if (!$studentProfile) {
            return response()->json(['message' => 'Student profile not found'], 404);
        }

        if ($studentProfile->picture && Storage::disk('public')->exists($studentProfile->picture)) {
            Storage::disk('public')->delete($studentProfile->picture);
        }

        $studentProfile->delete();

        return response()->json(['message' => 'Student profile deleted successfully']);
    }

    /**
     * Get the authenticated user's profile
     */
    public function myProfile()
    {
        $profile = StudentProfile::where('user_id', auth()->id())->with('user')->first();
        
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }
        
        return response()->json([
            'message' => 'Profile retrieved successfully',
            'data' => $profile
        ]);
    }

    /**
     * Check if authenticated user has a profile
     */
    public function hasProfile()
    {
        $hasProfile = StudentProfile::where('user_id', auth()->id())->exists();
        
        return response()->json(['has_profile' => $hasProfile]);
    }

    /**
     * Check profile completion status
     */
    public function profileCompletion()
    {
        $profile = StudentProfile::where('user_id', auth()->id())->first();
        
        if (!$profile) {
            return response()->json([
                'profile_exists' => false,
                'completion_percentage' => 0,
                'missing_fields' => ['picture', 'bio', 'university'],
                'is_complete' => false
            ]);
        }

        $fields = ['picture', 'bio', 'university'];
        $completedFields = [];
        $missingFields = [];

        foreach ($fields as $field) {
            if (!empty($profile->$field)) {
                $completedFields[] = $field;
            } else {
                $missingFields[] = $field;
            }
        }

        $completionPercentage = round((count($completedFields) / count($fields)) * 100);

        return response()->json([
            'profile_exists' => true,
            'completion_percentage' => $completionPercentage,
            'completed_fields' => $completedFields,
            'missing_fields' => $missingFields,
            'is_complete' => $completionPercentage === 100,
            'data' => $profile->load('user')
        ]);
    }

    /**
     * Search profiles by university
     */
    public function searchByUniversity(Request $request)
    {
        $request->validate([
            'university' => 'required|string|min:2'
        ]);

        $profiles = StudentProfile::where('university', 'like', '%' . $request->university . '%')
            ->with('user')
            ->get();

        if ($profiles->isEmpty()) {
            return response()->json(['message' => 'No profiles found for this university'], 404);
        }

        return response()->json([
            'message' => 'Profiles retrieved successfully',
            'data' => $profiles
        ]);
    }

    /**
     * Update profile picture only
     */
    public function updatePicture(Request $request)
    {
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $profile = StudentProfile::where('user_id', auth()->id())->first();
        
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

   
        if ($profile->picture && Storage::disk('public')->exists($profile->picture)) {
            Storage::disk('public')->delete($profile->picture);
        }

        $profile->picture = $request->file('picture')->store('profile-pictures', 'public');
        $profile->save();

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'picture_url' => Storage::url($profile->picture)
        ]);
    }

    /**
     * Remove profile picture
     */
    public function removePicture()
    {
        $profile = StudentProfile::where('user_id', auth()->id())->first();
        
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        if ($profile->picture && Storage::disk('public')->exists($profile->picture)) {
            Storage::disk('public')->delete($profile->picture);
            $profile->picture = null;
            $profile->save();
        }

        return response()->json(['message' => 'Profile picture removed successfully']);
    }
}