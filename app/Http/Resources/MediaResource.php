<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
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
            'file_path' => $this->file_path,
            'media_type' => $this->media_type,
            'is_primary' => $this->is_primary,
            'url' => asset('storage/' . $this->file_path),
            'created_at' => $this->created_at,
            
            'is_image' => $this->media_type === 'image',
            'is_video' => $this->media_type === 'video',
            
            'file_extension' => $this->getFileExtension(),
            'file_name' => $this->getFileName(),
        ];
    }

    /**
     * Get file extension from path
     */
    protected function getFileExtension(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    
    protected function getFileName(): string
    {
        return pathinfo($this->file_path, PATHINFO_FILENAME);
    }
}