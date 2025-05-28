<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /* public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    } */

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'media' => $this->media->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => asset('storage/' . $media->file_path),
                    'type' => $media->media_type,
                    'is_primary' => $media->is_primary,
                ];
            }),
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
