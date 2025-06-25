<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            //
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s\-\.\']+$/u'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255'],
            'password' => ['sometimes', 'confirmed', 'min:12', 'max:50', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,50}$/'],
            'role' => ['sometimes', 'required', 'string', Rule::in(['admin', 'owner', 'student'])],
            'verification_status' => ['sometimes', 'string', Rule::in(['unverified', 'pending', 'verified'])],
            'verification_document' => ['sometimes', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
            '_method' => ['sometimes', 'string']
        ];
    }

     /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Name validation messages
            'name.string' => 'The name must be a valid text format.',
            'name.min' => 'The name must be at least 3 characters long.',
            'name.max' => 'The name cannot exceed 100 characters.',
            'name.regex' => 'The name may only contain letters, spaces, hyphens, dots, and apostrophes.',
            
            // Email validation messages
            'email.string' => 'The email must be a valid text format.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address cannot exceed 255 characters.',
            'email.unique' => 'This email address is already in use. Please choose a different email address.',
            
            // Password validation messages
            'password.confirmed' => 'The password confirmation does not match. Please confirm your password.',
            'password.min' => 'The password must be at least 12 characters long.',
            'password.max' => 'The password cannot exceed 50 characters.',
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            
            // Role validation messages
            'role.string' => 'The role must be a valid text format.',
            'role.in' => 'Please select a valid role: Admin, Owner, or Student.',
            
            // Verification status messages
            'verification_status.string' => 'The verification status must be a valid text format.',
            'verification_status.in' => 'The verification status must be either unverified, pending, or verified.',
            
            // Verification document messages
            'verification_document.file' => 'The verification document must be a valid file.',
            'verification_document.mimes' => 'The verification document must be a JPEG, JPG, PNG, or PDF file.',
            'verification_document.max' => 'The verification document size cannot exceed 5MB.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'role' => 'user role',
            'verification_status' => 'verification status',
            'verification_document' => 'verification document',
        ];
    }
}
