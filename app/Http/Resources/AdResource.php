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
            'picture_url' => $this->picture ? asset('storage/' . $this->picture) : null,
            'video_url' => $this->video ? asset('storage/' . $this->video) : null,
            'description' => $this->description,
            'price' => $this->price,
            'location' => $this->location,
            'space' => $this->space,
            'status' => $this->active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'amenities' => $this->amenities->pluck('name'),
            'owner' => [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ],
        ];
    }
}
