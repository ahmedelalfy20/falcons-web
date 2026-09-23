<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LeaderSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\p{L}\p{M}\s\.\'\-]+$/u'],
            'phone' => ['required', 'string', 'max:25', function (string $a, mixed $v, Closure $fail) {
                if (! PhoneNumber::isValid($v)) {
                    $fail(__('Please enter a valid phone number.'));
                }
            }],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=200,min_height=200'],
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('Please use letters only in your name.'),
            'email.unique' => __('An account with this email already exists. Please log in.'),
            'photo.required' => __('Please add a clear personal photo — it appears next to your name on the live leaderboard.'),
            'photo.image' => __('The photo must be an image (JPG, PNG or WebP).'),
            'photo.mimes' => __('The photo must be an image (JPG, PNG or WebP).'),
            'photo.max' => __('The photo must be smaller than 8 MB.'),
            'photo.dimensions' => __('The photo is too small. Please use one at least 200 × 200 pixels.'),
        ];
    }

    public function attributes(): array
    {
        return ['photo' => __('personal photo')];
    }
}
