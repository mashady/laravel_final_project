<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdRequest;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Models\AdMedia;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Ad::query()->with(['owner', 'amenities', 'media']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if (auth()->check() && (auth()->user()->role === 'owner' || auth()->user()->role === 'admin')) {
            if ($request->has('status')) {
                $query->where('active', $request->status);
            }
        } else {
            $query->where('active', 'published');
        }

        $ads = $query->latest()->paginate(10);

        return AdResource::collection($ads);
    }

    
    public function store(StoreAdRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = auth()->id();
        
        $ad = Ad::create($data);
        
        if ($request->has('media')) {
            $this->processMedia($request->media, $ad);
        }
        
        if ($request->has('amenities')) {
            $ad->amenities()->sync($request->amenities);
        }
        
        return new AdResource($ad->load(['owner', 'amenities', 'media']));
    }

    
    public function show(Ad $ad)
    {
        if ($ad->active !== 'published' && 
            (!auth()->check() || (auth()->id() !== $ad->owner_id && auth()->user()->role !== 'admin'))) {
            return response()->json(['message' => 'Not found'], 404);
        }
        
        return new AdResource($ad->load(['owner', 'amenities', 'media']));
    }

    
    public function update(StoreAdRequest $request, Ad $ad)
    {
        if (auth()->id() !== $ad->owner_id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $ad->update($request->validated());
        
        if ($request->has('media')) {
            $this->processMedia($request->media, $ad);
        }
        
        if ($request->has('amenities')) {
            $ad->amenities()->sync($request->amenities);
        }
        
        return new AdResource($ad->load(['owner', 'amenities', 'media']));
    }

    
    public function destroy(Ad $ad)
    {
        if (auth()->id() !== $ad->owner_id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $ad->media->each(function ($media) {
            Storage::disk('public')->delete($media->file_path);
            $media->delete();
        });
        
        $ad->delete();
        
        return response()->json(['message' => 'Ad deleted successfully']);
    }

    
    public function amenities()
    {
        $amenities = Amenity::all(['id', 'name']);
        return response()->json($amenities);
    }

    
    protected function processMedia(array $mediaItems, Ad $ad)
    {
        foreach ($mediaItems as $mediaItem) {
            $file = $mediaItem['file'];
            $isPrimary = $mediaItem['is_primary'] ?? false;
            
            $path = $file->store('ads/media', 'public');
            $type = in_array($file->extension(), ['mp4', 'mov', 'avi']) ? 'video' : 'image';
            
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

   
    public function destroyMedia(Ad $ad, AdMedia $media)
    {
        if (auth()->id() !== $ad->owner_id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($media->ad_id !== $ad->id) {
            return response()->json(['message' => 'Media does not belong to this ad'], 400);
        }

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }
}