<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UpdateAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

     
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:apartment,room,bed',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'area' => 'nullable|string|min:0',
            'street' => 'nullable|string|max:255',
            'block' => 'nullable|string|max:255',
            'number_of_beds' => 'nullable|integer|min:0',
            'number_of_bathrooms' => 'nullable|integer|min:0',
            'space' => 'required|numeric|min:0',
    
            'media' => 'nullable|array',
            'media.*' => [
            'nullable',
            'file',
            'max:20480', // 20MB
            'mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime'
             ],
        
        'existing_media' => 'nullable|array',
        'existing_media.*' => 'integer|exists:media,id',
        ];
    }
}
