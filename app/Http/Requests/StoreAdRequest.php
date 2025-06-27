<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        'title' => 'required|string|max:255',
        'type' => ['required', Rule::in(['apartment', 'room', 'bed'])],
        'description' => 'required|string|min:20',
        'price' => 'required|numeric|min:1',
        'space' => 'required|numeric|min:1',
        'number_of_beds' => 'required|integer|min:1',
        'number_of_bathrooms' => 'required|integer|min:1',
        'area' => 'required|string|max:100',     
        'street' => 'required|string|max:100',   
        'block' => 'required|string|max:50',    
        
        'media' => 'required|array|min:1',
        'media.*' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:10240',
        'primary_media_index' => 'required|integer|min:0',
    ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Ad title is required',
            'type.required' => 'Property type is required',
            'type.in' => 'Property type must be apartment, room or bed',
            'description.required' => 'Description is required',
            'price.required' => 'Price is required',
            'price.min' => 'Price must be at least 1',
            'space.required' => 'Space size is required',
            'space.min' => 'Space must be at least 1',
            'number_of_beds.required' => 'Number of beds is required',
            'number_of_beds.min' => 'At least 1 bed is required',
            'number_of_bathrooms.required' => 'Number of bathrooms is required',
            'number_of_bathrooms.min' => 'At least 1 bathroom is required',
            'area.required' => 'Area name is required',
            'street.required' => 'Street name is required',
            'block.required' => 'Block number is required',
            'media.required' => 'At least one media file is required',
            'media.min' => 'At least one media file is required',
            'media.*.required' => 'Each media file is required',
            'media.*.mimes' => 'Only images (JPG, PNG) and videos (MP4, MOV, AVI) are allowed',
            'media.*.max' => 'Each file must be less than 10MB',
            'primary_media_index.required' => 'Primary media selection is required',
        ];
    }
}