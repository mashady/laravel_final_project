<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\MediaResource;

/**
 * Class AdResource
 *
 * Transforms Ad model data for API responses.
 */
class AdResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
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
            'street' => $this->street,
            'area' => $this->area,
            'block' => $this->block,
            'number_of_beds' => $this->number_of_beds,
            'number_of_bathrooms' => $this->number_of_bathrooms,
            'space' => $this->space,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'primary_image' => $this->when($this->relationLoaded('media'), function () {
                $primaryImage = $this->media->where('media_type', 'image')->where('is_primary', true)->first();
                
                return [
                    'ad_id' => $this->id,
                    'created_at' => $primaryImage->created_at,
                    'file_path' => $primaryImage->file_path,
                    'id' => $primaryImage->id,
                    'is_primary' => $primaryImage->is_primary,
                    'media_type' => $primaryImage->media_type,
                    'updated_at' => $primaryImage->updated_at,
                ];
            }),
            'primary_video' => $this->getPrimaryVideo(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            
            'media_count' => [
                'total' => $this->relationLoaded('media') ? $this->media->count() : 0,
                'images' => $this->relationLoaded('media') ? $this->media->where('media_type', 'image')->count() : 0,
                'videos' => $this->relationLoaded('media') ? $this->media->where('media_type', 'video')->count() : 0,
            ],
            
            'owner' => $this->when($this->relationLoaded('owner') && $this->owner, function () {
                return [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->when(
                        $this->shouldShowOwnerEmail(),
                        $this->owner->email
                    ),
                    'owner_profile' => $this->owner->ownerProfile,
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

    /**
     * Get the primary image URL or null.
     */
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

    /**
     * Get the primary video URL or null.
     */
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

    /**
     * Determine if the owner email should be shown.
     */
    protected function shouldShowOwnerEmail(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()->role === 'admin' || 
               auth()->id() === $this->owner_id;
    }

    /**
     * Format the price with currency.
     */
    protected function formatPrice(): string
    {
        return number_format($this->price, 2) . ' EGP';
    }

    /**
     * Calculate price per square meter.
     */
    protected function calculatePricePerSquareMeter(): ?string
    {
        if (!$this->space || $this->space <= 0) {
            return null;
        }

        $pricePerSqm = $this->price / $this->space;
        return number_format($pricePerSqm, 2) . ' EGP/m²';
    }

    /**
     * Determine if the current user can edit the ad.
     */
    protected function canUserEdit(): bool
    {
        return auth()->id() === $this->owner_id || 
               auth()->user()->role === 'admin';
    }

    /**
     * Determine if the current user can delete the ad.
     */
    protected function canUserDelete(): bool
    {
        return auth()->id() === $this->owner_id || 
               auth()->user()->role === 'admin';
    }
}
