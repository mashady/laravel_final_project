<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class StudentProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate completion status
        $fields = ['picture', 'bio', 'university'];
        $completedFields = [];
        
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completedFields[] = $field;
            }
        }
        
        $completionPercentage = round((count($completedFields) / count($fields)) * 100);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'picture' => $this->picture ? Storage::url($this->picture) : null,
            'picture_path' => $this->picture,
            'bio' => $this->bio,
            'university' => $this->university,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Completion status
            'completion_percentage' => $completionPercentage,
            'is_complete' => $completionPercentage === 100,
            'completed_fields' => $completedFields,
            'missing_fields' => array_diff($fields, $completedFields),
            
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'email_verified_at' => $this->user->email_verified_at,
                    'created_at' => $this->user->created_at,
                ];
            }),
        ];
    }
}