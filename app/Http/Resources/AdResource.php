<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'description' => $this->description,
            'price' => $this->price,
            'location' => $this->location,
            'space' => $this->space,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'primary_image' => $this->getPrimaryImage(),
            'primary_video' => $this->getPrimaryVideo(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            
            'media_count' => [
                'total' => $this->whenLoaded('media', function () {
                    return $this->media->count();
                }, 0),
                'images' => $this->whenLoaded('media', function () {
                    return $this->media->where('media_type', 'image')->count();
                }, 0),
                'videos' => $this->whenLoaded('media', function () {
                    return $this->media->where('media_type', 'video')->count();
                }, 0),
            ],
            
            'owner' => $this->when($this->relationLoaded('owner'), function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->when(
                        $this->shouldShowOwnerEmail(),
                        $this->owner->email
                    ),
                ];
            }),
            
            'is_published' => $this->status === 'published',
            'is_pending' => $this->status === 'pending',
            'formatted_price' => $this->formatPrice(),
            'price_per_sqm' => $this->calculatePricePerSquareMeter(),
            
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
            
            'can_edit' => $this->when(auth()->check(), function () {
                return $this->canUserEdit();
            }),
            'can_delete' => $this->when(auth()->check(), function () {
                return $this->canUserDelete();
            }),
        ];
    }

    
    protected function getPrimaryImage(): ?string
    {
        if (!$this->relationLoaded('media')) {
            return null;
        }

        $primaryImage = $this->media
            ->where('media_type', 'image')
            ->where('is_primary', true)
            ->first();

        if (!$primaryImage) {
            $primaryImage = $this->media
                ->where('media_type', 'image')
                ->first();
        }

        return $primaryImage ? asset('storage/' . $primaryImage->file_path) : null;
    }

    
    protected function getPrimaryVideo(): ?string
    {
        if (!$this->relationLoaded('media')) {
            return null;
        }

        $primaryVideo = $this->media
            ->where('media_type', 'video')
            ->where('is_primary', true)
            ->first();

        if (!$primaryVideo) {
            $primaryVideo = $this->media
                ->where('media_type', 'video')
                ->first();
        }

        return $primaryVideo ? asset('storage/' . $primaryVideo->file_path) : null;
    }

    
    protected function shouldShowOwnerEmail(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()->role === 'admin' || 
               auth()->id() === $this->owner_id;
    }

    
    protected function formatPrice(): string
    {
        return number_format($this->price, 2) . ' EGP';
    }

    
    protected function calculatePricePerSquareMeter(): ?string
    {
        if (!$this->space || $this->space <= 0) {
            return null;
        }

        $pricePerSqm = $this->price / $this->space;
        return number_format($pricePerSqm, 2) . ' EGP/m²';
    }

   
    protected function canUserEdit(): bool
    {
        return auth()->id() === $this->owner_id || 
               auth()->user()->role === 'admin';
    }

    
    protected function canUserDelete(): bool
    {
        return auth()->id() === $this->owner_id || 
               auth()->user()->role === 'admin';
    }
}