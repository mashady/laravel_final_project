<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OwnerStoreRequest extends FormRequest
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
            'picture' => 'nullable|image|max:2048',
            'bio' => 'nullable|string|max:1000',
        ];
    }
    
    public function messages(): array
    {
        return [
            'picture.image' => 'The picture must be an image file.',
            'picture.max' => 'The picture size should not exceed 2MB.',
            'bio.string' => 'The bio must be a valid text format.',
            'bio.max' => 'The bio cannot exceed 1000 characters.',
        ];
    }
    
    
}
