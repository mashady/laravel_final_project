<?php

namespace App\Http\Controllers;
 
use App\Http\Requests\OwnerStoreRequest;
use App\Http\Requests\OwnerUpdadteRequest;
use App\Models\OwnerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OwnerController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $owners = OwnerProfile::with('user')->get();
        if ($owners->isEmpty()) {
            return response()->json(['message' => 'No owner profiles found'], 404);
        }
        return response()->json($owners);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OwnerStoreRequest $request)
    {
        $validated = $request->validated();
    
        if ($request->hasFile('picture')) {
            $path = $request->file('picture')->store('pictures', 'public');
            $validated['picture'] = $path;
        }
    
        $validated['user_id'] = $request->user()->id;
        //check role
        if ($request->user()->role !== 'owner') {
            return response()->json(['message' => 'User is not an owner'], 400);
        }

        //check if the user already has a profile
        $existingProfile = OwnerProfile::where('user_id', $request->user()->id)->first();
        if ($existingProfile) {
            return response()->json(['message' => 'User already has a profile'], 400);
        }

        $profile = OwnerProfile::create($validated);
    
        return response()->json([
            'message' => 'Owner profile created successfully',
            'data' => $profile
        ], 201);
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    
        $profile = OwnerProfile::where('user_id', $id)->with('user')->first();
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
    public function update(OwnerStoreRequest $request)
    {
        $user = $request->user();
    
        $profile = OwnerProfile::where('user_id', $user->id)->first();
    
        if (!$profile) {
            return response()->json(['message' => 'Profile not found'], 404);
        }
    
        //check if the user is an owner and this proofile belongs to them
        if ($user->role !== 'owner' || $profile->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $this->authorize('update', $profile);

        $validated = $request->validated();
    
        if ($request->hasFile('picture')) {
            if ($profile->picture && Storage::disk('public')->exists($profile->picture)) {
                Storage::disk('public')->delete($profile->picture);
            }
    
            $path = $request->file('picture')->store('pictures', 'public');
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
        $ownerProfile = OwnerProfile::where('user_id', $id)->first();
        if (!$ownerProfile) {
            return response()->json(['message' => 'Owner profile not found'], 404);
        }

        if ($ownerProfile->picture && Storage::disk('public')->exists($ownerProfile->picture)) {
            Storage::disk('public')->delete($ownerProfile->picture);
        }

        $ownerProfile->delete();

        return response()->json(['message' => 'Owner profile deleted successfully']);
    }
}
