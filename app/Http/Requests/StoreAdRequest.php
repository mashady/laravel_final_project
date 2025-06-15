<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdRequest extends FormRequest
{
   
    /* public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    } */

    
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:apartment,room,bed', 
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
          'area' => '|string|min:0',
          'street' => '|string|max:255',
          'block' => '|string|max:255',
          'number_of_beds' => 'integer|min:0',
          'number_of_bathrooms' => 'integer|min:0',
            'space' => 'required|numeric|min:0',
            /* 'status' => 'required|in:active,inactive', */
            /* 'owner_id' => 'required|exists:users,id', */
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,mp4|max:20480',

        ];
    }
}