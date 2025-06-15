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

class AdController extends Controller
{
   
    public function index(Request $request)
    {
        $query = Ad::query()->with(['owner', 'media']);

        if ($request->has('type') && in_array($request->type, ['apartment', 'room', 'bed'])) {
            $query->where('type', $request->type);
        }

        if ($request->has('min_price') && is_numeric($request->min_price)) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price') && is_numeric($request->max_price)) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('min_space') && is_numeric($request->min_space)) {
            $query->where('space', '>=', $request->min_space);
        }

        if ($request->has('location') && !empty($request->location)) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        /* if (auth()->check() && (auth()->user()->role === 'owner' || auth()->user()->role === 'admin')) {
            if ($request->has('status') && in_array($request->status, ['pending', 'published', 'closed', 'rejected', 'cancelled'])) {
                $query->where('status', $request->status);
            }
            
            if (auth()->user()->role === 'owner' && $request->has('my_ads')) {
                $query->where('owner_id', auth()->id());
            }
        } else {
            $query->where('status', 'published');
        } */

        $ads = $query->latest()->paginate($request->get('per_page', 10));

        return AdResource::collection($ads);
    }

    
    public function store(StoreAdRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            #$data['owner_id'] = auth()->id();
            $data['owner_id'] = 1;
            
            // Set default status based on user role
            /* if (!isset($data['status'])) {
                $data['status'] = auth()->user()->role === 'admin' ? 'published' : 'pending';
            } */
            
            $user = Auth::user();
            $subscription = $user->subscription()->with('plan')->first();

            if (!$subscription || !$subscription->active) {
                return response()->json(['message' => 'You need an active subscription to add ads.'], 403);
            }
        
            $planLimit = $subscription->plan->ads_Limit;
        
            $userAdCount = $user->ads()->count();
        
            if ($userAdCount >= $planLimit) {
                return response()->json(['message' => 'You have reached your ad limit for this plan.'], 403);
            }

            $ad = Ad::create($data);
            
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

    
    public function update(StoreAdRequest $request, Ad $ad)
    {
        /* if (!$this->canModifyAd($ad)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        } */
        
        try {
            DB::beginTransaction();
            
            $ad->update($request->validated());
            
            if ($request->hasFile('media')) {
                $this->processMedia($request->file('media'), $ad, $request->get('primary_media_index', 0));
            }
            
            DB::commit();
            
            return new AdResource($ad->load(['owner', 'media']));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating ad: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error updating ad',
                'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong'
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
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/mov', 'video/avi'];
        $maxFileSize = 10 * 1024 * 1024; 

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
}