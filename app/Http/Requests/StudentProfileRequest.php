<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
  

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bio' => 'nullable|string|max:1000',
            'university' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'picture.image' => 'The picture must be an image file.',
            'picture.mimes' => 'The picture must be a file of type: jpeg, png, jpg, gif.',
            'picture.max' => 'The picture may not be greater than 2MB.',
            'bio.max' => 'The bio may not be greater than 1000 characters.',
            'university.max' => 'The university name may not be greater than 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'picture' => 'profile picture',
            'bio' => 'biography',
            'university' => 'university name',
        ];
    }
}