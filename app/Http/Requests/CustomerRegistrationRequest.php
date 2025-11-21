<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CustomerRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:customers,email',
                'unique:users,email',
            ],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^(?:\+?63|0)9\d{9}$/'],
            'address' => ['nullable', 'string'],
            'location' => ['nullable', 'array'],
            'location.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'name',
            'company_name' => 'company name',
            'email' => 'email address',
            'phone' => 'phone number',
            'address' => 'address',
            'profile_image' => 'profile picture',
            'password' => 'password',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'name.max' => 'Name is too long (max 255 characters).',

            'company_name.max' => 'Company name is too long (max 255 characters).',

            'email.required' => 'Please enter an email address.',
            'email.email' => 'That doesn\'t look like a valid email.',
            'email.max' => 'Email is too long (max 255 characters).',
            'email.unique' => 'This email is already in use.',

            'phone.max' => 'Phone number is too long (max 50 characters).',
            'phone.regex' => 'Please enter a valid Philippine mobile number (09XXXXXXXXX or +639XXXXXXXXX).',

            'location.array' => 'Location must be a valid location.',
            'location.lat.numeric' => 'Latitude must be a number between -90 and 90.',
            'location.lat.between' => 'Latitude must be between -90 and 90.',
            'location.lng.numeric' => 'Longitude must be a number between -180 and 180.',
            'location.lng.between' => 'Longitude must be between -180 and 180.',

            'profile_image.image' => 'Please upload a valid image file.',
            'profile_image.max' => 'Image is too large (max 2 MB).',

            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}

