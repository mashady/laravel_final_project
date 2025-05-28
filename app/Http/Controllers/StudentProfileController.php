<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Http\Requests\StoreStudentProfileRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Http\Resources\StudentProfileResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StudentProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profiles = StudentProfile::with('user')->paginate(10);
        return StudentProfileResource::collection($profiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentProfileRequest $request)
    {
        $validated = $request->validated();
        
        // Handle picture upload if present
        if ($request->hasFile('picture')) {
            $validated['picture'] = $request->file('picture')->store('profile-pictures', 'public');
        }
        
        // Add authenticated user's ID
        $validated['user_id'] = Auth::id();
        
        $profile = StudentProfile::create($validated);
        
        return new StudentProfileResource($profile->load('user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(StudentProfile $studentProfile)
    {
        return new StudentProfileResource($studentProfile->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentProfileRequest $request, StudentProfile $studentProfile)
    {
        if ($studentProfile->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validated();
        
       
        if ($request->hasFile('picture')) {
            if ($studentProfile->picture) {
                Storage::disk('public')->delete($studentProfile->picture);
            }
            $validated['picture'] = $request->file('picture')->store('profile-pictures', 'public');
        }
        
        $studentProfile->update($validated);
        
        return new StudentProfileResource($studentProfile->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudentProfile $studentProfile)
    {
        if ($studentProfile->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($studentProfile->picture) {
            Storage::disk('public')->delete($studentProfile->picture);
        }
        
        $studentProfile->delete();
        
        return response()->json(['message' => 'Profile deleted successfully']);
    }

    /**
     * Get the authenticated user's profile
     */
    public function myProfile()
    {
        $profile = StudentProfile::where('user_id', Auth::id())->with('user')->first();
        
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }
        
        return new StudentProfileResource($profile);
    }

    /**
     * Check if authenticated user has a profile
     */
    public function hasProfile()
    {
        $hasProfile = StudentProfile::where('user_id', Auth::id())->exists();
        
        return response()->json(['has_profile' => $hasProfile]);
    }

    /**
     * Check profile completion status
     */
    public function profileCompletion()
    {
        $profile = StudentProfile::where('user_id', Auth::id())->first();
        
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
            'profile' => new StudentProfileResource($profile->load('user'))
        ]);
    }

    /**
     * Complete profile step by step
     */
    public function completeProfileStep(Request $request)
    {
        $request->validate([
            'step' => 'required|string|in:picture,bio,university',
            'value' => 'required',
        ]);

        $profile = StudentProfile::firstOrCreate(
            ['user_id' => Auth::id()],
            ['user_id' => Auth::id()]
        );

        $step = $request->step;
        $value = $request->value;

        // Handle different step types
        switch ($step) {
            case 'picture':
                $request->validate(['value' => 'image|mimes:jpeg,png,jpg,gif|max:2048']);
                
                // Delete old picture if exists
                if ($profile->picture) {
                    Storage::disk('public')->delete($profile->picture);
                }
                
                $profile->picture = $request->file('value')->store('profile-pictures', 'public');
                break;
                
            case 'bio':
                $request->validate(['value' => 'string|max:1000']);
                $profile->bio = $value;
                break;
                
            case 'university':
                $request->validate(['value' => 'string|max:255']);
                $profile->university = $value;
                break;
        }

        $profile->save();

        return response()->json([
            'message' => ucfirst($step) . ' updated successfully',
            'profile' => new StudentProfileResource($profile->load('user'))
        ]);
    }

    /**
     * Get profile by user ID (for viewing other profiles)
     */
    public function getProfileByUserId($userId)
    {
        $profile = StudentProfile::where('user_id', $userId)->with('user')->first();
        
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }
        
        return new StudentProfileResource($profile);
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
            ->paginate(10);

        return StudentProfileResource::collection($profiles);
    }

    /**
     * Update profile picture only
     */
    public function updatePicture(Request $request)
    {
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $profile = StudentProfile::where('user_id', Auth::id())->first();
        
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        // Delete old picture if exists
        if ($profile->picture) {
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
        $profile = StudentProfile::where('user_id', Auth::id())->first();
        
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }

        if ($profile->picture) {
            Storage::disk('public')->delete($profile->picture);
            $profile->picture = null;
            $profile->save();
        }

        return response()->json(['message' => 'Profile picture removed successfully']);
    }

    /**
     * Get profile statistics
     */
    public function profileStats()
    {
        $totalProfiles = StudentProfile::count();
        $completedProfiles = StudentProfile::whereNotNull('picture')
            ->whereNotNull('bio')
            ->whereNotNull('university')
            ->count();
        
        $profilesWithPicture = StudentProfile::whereNotNull('picture')->count();
        $profilesWithBio = StudentProfile::whereNotNull('bio')->count();
        $profilesWithUniversity = StudentProfile::whereNotNull('university')->count();

        return response()->json([
            'total_profiles' => $totalProfiles,
            'completed_profiles' => $completedProfiles,
            'completion_rate' => $totalProfiles > 0 ? round(($completedProfiles / $totalProfiles) * 100) : 0,
            'profiles_with_picture' => $profilesWithPicture,
            'profiles_with_bio' => $profilesWithBio,
            'profiles_with_university' => $profilesWithUniversity,
        ]);
    }

    /**
     * Bulk update profile fields
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'bio' => 'nullable|string|max:1000',
            'university' => 'nullable|string|max:255',
        ]);

        $profile = StudentProfile::firstOrCreate(
            ['user_id' => Auth::id()],
            ['user_id' => Auth::id()]
        );

        $updated = false;
        
        if ($request->has('bio')) {
            $profile->bio = $request->bio;
            $updated = true;
        }
        
        if ($request->has('university')) {
            $profile->university = $request->university;
            $updated = true;
        }

        if ($updated) {
            $profile->save();
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => new StudentProfileResource($profile->load('user'))
        ]);
    }
}