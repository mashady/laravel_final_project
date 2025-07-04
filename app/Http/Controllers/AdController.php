<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Models\AdMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Media;
use App\Http\Requests\UpdateAdRequest;
use App\Services\MapboxService;

class AdController extends Controller
{
       public function userAds(Request $request)
{
    $userId = Auth::id();

    $validated = $request->validate([
        'type' => 'sometimes|in:apartment,room,bed',
        'min_price' => 'sometimes|numeric|min:0',
        'max_price' => 'sometimes|numeric|min:0|gt:min_price',
        'min_space' => 'sometimes|numeric|min:0',
        'location' => 'sometimes|string|max:255',
        'per_page' => 'sometimes|integer|min:1|max:100'
    ]);

    $query = Ad::with(['owner', 'media'])
        ->where('owner_id', $userId);  

    if ($request->has('type')) {
        $query->where('type', $validated['type']);
    }

    if ($request->has('min_price')) {
        $query->where('price', '>=', $validated['min_price']);
    }

    if ($request->has('max_price')) {
        $query->where('price', '<=', $validated['max_price']);
    }

    if ($request->has('min_space')) {
        $query->where('space', '>=', $validated['min_space']);
    }

    if ($request->has('location')) {
        $query->where('location', 'LIKE', '%' . $validated['location'] . '%');
    }

    $perPage = $request->get('per_page', 3);
    $ads = $query->latest()->paginate($perPage);

    return AdResource::collection($ads);
}

public function index(Request $request)
{
    $query = Ad::query()->with(['owner', 'media']);

    // Modified all text filters to use case-insensitive search
    if ($request->has('title') && !empty($request->title)) {
        $query->where('title', 'ILIKE', '%' . $request->title . '%');
    }
    if ($request->has('type') && in_array(strtolower($request->type), ['apartment', 'room', 'bed'])) {
        $query->where('type', strtolower($request->type));
    }
    if ($request->has('description') && !empty($request->description)) {
        $query->where('description', 'ILIKE', '%' . $request->description . '%');
    }
    if ($request->has('min_price') && is_numeric($request->min_price)) {
        $query->where('price', '>=', $request->min_price);
    }
    if ($request->has('max_price') && is_numeric($request->max_price)) {
        $query->where('price', '<=', $request->max_price);
    }
    if ($request->has('area') && !empty($request->area)) {
        $query->where('area', 'ILIKE', '%' . $request->area . '%');
    }
    if ($request->has('street') && !empty($request->street)) {
        $query->where('street', 'ILIKE', '%' . $request->street . '%');
    }
    if ($request->has('block') && !empty($request->block)) {
        $query->where('block', 'ILIKE', '%' . $request->block . '%');
    }
    if ($request->has('number_of_beds') && is_numeric($request->number_of_beds)) {
        $query->where('number_of_beds', $request->number_of_beds);
    }
    if ($request->has('number_of_bathrooms') && is_numeric($request->number_of_bathrooms)) {
        $query->where('number_of_bathrooms', $request->number_of_bathrooms);
    }
    if ($request->has('min_space') && is_numeric($request->min_space)) {
        $query->where('space', '>=', $request->min_space);
    }
    
    // Sorting
    $sortBy = $request->get('sort_by', 'created_at');
    $sortDir = $request->get('sort_dir', 'desc');
    $allowedSorts = ['created_at', 'price', 'space', 'number_of_beds', 'number_of_bathrooms'];
    if (!in_array($sortBy, $allowedSorts)) {
        $sortBy = 'created_at';
    }
    if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
        $sortDir = 'desc';
    }
    $query->orderBy($sortBy, $sortDir);

    $ads = $query->paginate($request->get('per_page', 6));

    return AdResource::collection($ads);
}

    
    public function store(StoreAdRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $data['owner_id'] = auth()->id();
            
            
            $user = User::findOrFail($data['owner_id']);
            $subscription = $user->subscription;

            if (!$subscription || !$subscription->active) {
                return response()->json(['message' => 'You need an active subscription to add ads.'], 403);
            }
        
            $plan = $subscription->plan;
            $expirationDate = $subscription->created_at->addDays($plan->duration);
    
            if (now()->greaterThan($expirationDate)) {
                $subscription->active = false;
                $subscription->save();
    
                return response()->json(['message' => 'Your subscription has expired. Please renew or upgrade.'], 403);
            }
    
            if (!$subscription->active) {
                return response()->json(['message' => 'Your subscription is not active.'], 403);
            }
    
            if ($subscription->ads_remain >= $plan->ads_Limit) {
                $subscription->active = False;
                $subscription->save();
    
                return response()->json([
                    'message' => 'You have reached the ad limit for your plan. Your subscription is now inactive.',
                ], 403);
            }

            $ad = Ad::create($data);

            $subscription->increment('ads_remain');
            
            if ($request->hasFile('media')) {
                $this->processMedia($request->file('media'), $ad, $request->get('primary_media_index', 0));
            }
            
            DB::commit();
            
            return new AdResource($ad->load(['owner', 'media']));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating ad: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error creating ad',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong'
            ], 500);
        }
    }


    
    public function show(Ad $ad)
    {
        return new AdResource($ad->load(['owner', 'media']));
    }

    
    public function update(UpdateAdRequest $request, Ad $ad)
    {
        try {
            DB::beginTransaction();
            
            // Update basic fields
            $ad->update($request->except(['media', 'existing_media']));
            
            // Handle media updates
            $existingMediaIds = $request->input('existing_media', []);
            
            // Delete any media not in the existing_media array
            $ad->media()->whereNotIn('id', $existingMediaIds)->delete();
            
            // Process new uploads
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $file) {
                    // Validate each file individually
                    if ($file->getSize() > 20 * 1024 * 1024) {
                        throw new \Exception("File too large: " . $file->getClientOriginalName());
                    }
                    
                    $media = $ad->media()->create([
                        'file_path' => $file->store('media', 'public'),
                        'media_type' => str_starts_with($file->getMimeType(), 'image') ? 'image' : 'video'
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'data' => new AdResource($ad->load('media'))
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating ad: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    
    public function destroy(Ad $ad)
    {
        /* if (!$this->canModifyAd($ad)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } */
        
        try {
            DB::beginTransaction();
            
            $ad->media->each(function ($media) {
                Storage::disk('public')->delete($media->file_path);
            });
            
            $ad->delete();
            
            DB::commit();
            
            return response()->json(['message' => 'Ad deleted successfully']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ad: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error deleting ad',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong'
            ], 500);
        }
    }

    
    public function destroyMedia(Ad $ad, AdMedia $media)
    {
        if (!$this->canModifyAd($ad)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($media->ad_id !== $ad->id) {
            return response()->json(['message' => 'Media does not belong to this ad'], 400);
        }

        try {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();

            return response()->json(['message' => 'Media deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error deleting media: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error deleting media',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong'
            ], 500);
        }
    }

    
    public function updateStatus(Request $request, Ad $ad)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,published,closed,rejected,cancelled'
        ]);

        $ad->update(['status' => $request->status]);

        return new AdResource($ad->load(['owner', 'media']));
    }

   
    protected function processMedia(array $mediaFiles, Ad $ad, int $primaryIndex = 0)
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/mov', 'video/avi', 'video/webm'];
        $maxFileSize = 20 * 1024 * 1024; 

        foreach ($mediaFiles as $index => $file) {
            if (!$file->isValid()) {
                throw ValidationException::withMessages(['media' => 'Invalid file at index ' . $index]);
            }

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw ValidationException::withMessages(['media' => 'Invalid file type at index ' . $index]);
            }

            if ($file->getSize() > $maxFileSize) {
                throw ValidationException::withMessages(['media' => 'File too large at index ' . $index]);
            }

            $path = $file->store('ads/media', 'public');
            $type = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';
            $isPrimary = ($index === $primaryIndex);

            if ($isPrimary) {
                $ad->media()->update(['is_primary' => false]);
            }

            $ad->media()->create([
                'file_path' => $path,
                'media_type' => $type,
                'is_primary' => $isPrimary,
            ]);
        }
    }

    
    protected function canViewAd(Ad $ad): bool
    {
        if ($ad->status === 'published') {
            return true;
        }

        if (!auth()->check()) {
            return false;
        }

        return auth()->id() === $ad->owner_id || auth()->user()->role === 'admin';
    }

    
    protected function canModifyAd(Ad $ad): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->id() === $ad->owner_id || auth()->user()->role === 'admin';
    }

    public function deleteAllMedia($id)
    {
        $ad = Ad::findOrFail($id);
    
        foreach ($ad->media as $media) {
            // Delete from disk (adjust path if necessary)
            Storage::delete($media->file_path);
            $media->delete();
        }
    
        return response()->json(['message' => 'All media deleted successfully.']);
    }

    public function deleteOneMedia($id)
    {
        $media = Media::findOrFail($id);

        // Delete file from storage
        Storage::delete($media->file_path);
        // Delete from DB
        $media->delete();

        return response()->json(['message' => 'Media deleted successfully.']);
    }

public function nearUniversity(Request $request)
{
    $request->validate([
        'university' => 'required|string',
        'radius' => 'sometimes|numeric|min:1|max:20'
    ]);

    $universityLocation = MapboxService::geocodeAddress($request->university);

    if (!$universityLocation) {
        return response()->json(['error' => 'University location not found'], 404);
    }

    $radius = 50;

    // Step 1: Raw distance calculation
    $rawDistanceSql = "
        *, 
        (6371 * acos(
            cos(radians(?)) * 
            cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + 
            sin(radians(?)) * 
            sin(radians(latitude))
        )) AS distance
    ";

    // Step 2: Create a subquery with distance as alias
    $subQuery = DB::table('ads')
        ->selectRaw($rawDistanceSql, [
            $universityLocation['latitude'],
            $universityLocation['longitude'],
            $universityLocation['latitude'],
        ]);

    // Step 3: Use the subquery and filter on distance
    $properties = DB::table(DB::raw("({$subQuery->toSql()}) as sub"))
        ->mergeBindings($subQuery)
        ->where('distance', '<', $radius)
        ->orderBy('distance')
        ->get();

    return response()->json([
        'properties' => $properties,
        'university' => [
            'latitude' => $universityLocation['latitude'],
            'longitude' => $universityLocation['longitude'],
            'name' => $request->university,
        ]
    ]);
}



}