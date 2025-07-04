<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[\pL\s\-\.\']+$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:12', 'max:50', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,50}$/'],
            'role' => ['required', 'string', Rule::in(['admin', 'owner', 'student'])],
            'verification_status' => ['sometimes', 'string', Rule::in(['unverified', 'pending', 'verified'])],
            'verification_document' => ['nullable'],
            'profile_image' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            // 
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a valid text format.',
            'name.min' => 'The name must be at least 3 characters long.',
            'name.max' => 'The name cannot exceed 100 characters.',
            'name.regex' => 'The name may only contain letters, spaces, hyphens, dots, and apostrophes.',
            
            'email.required' => 'The email address is required.',
            'email.string' => 'The email must be a valid text format.',
            'email.email' => 'Please provide a valid email address.',
            'email.max' => 'The email address cannot exceed 255 characters.',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',
            
            'password.required' => 'A password is required.',
            'password.min' => 'The password must be at least 12 characters long.',
            'password.max' => 'The password cannot exceed 50 characters.',
            'password.regex' => 'The password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
            'password.confirmed' => 'The password confirmation does not match. Please confirm your password.',
            
            'role.required' => 'Please select a user role.',
            'role.string' => 'The role must be a valid text format.',
            'role.in' => 'Please select a valid role: Admin, Owner, or Student.',
            
            'verification_status.string' => 'The verification status must be a valid text format.',
            'verification_status.in' => 'The verification status must be either unverified, pending, or verified.',
        ];
    }

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
