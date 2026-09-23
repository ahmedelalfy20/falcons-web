<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public form — business rules are enforced in RegistrationService
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'full_name' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('full_name'))),
            'city' => trim(preg_replace('/\s+/u', ' ', (string) $this->input('city'))),
            'team' => trim((string) $this->input('team')),
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
        ]);
    }

    public function rules(): array
    {
        $competition = \App\Models\Competition::active();
        $requireEmail = (bool) ($competition?->config('require_email') ?? true);
        $requireProof = (bool) ($competition?->config('require_transfer_proof') ?? true);

        return [
            'ref' => ['required', 'string', 'max:20'],
            'full_name' => ['required', 'string', 'min:3', 'max:120', 'regex:/^[\p{L}\p{M}\s\.\'\-]+$/u'],
            'phone' => ['required', 'string', 'max:25', function (string $attr, mixed $value, Closure $fail) {
                if (! PhoneNumber::isValid($value)) {
                    $fail(__('Please enter a valid phone number.'));
                }
            }],
            'email' => [$requireEmail ? 'required' : 'nullable', 'email:rfc', 'max:190'],
            'city' => ['required', 'string', 'min:2', 'max:80'],
            'team' => ['required', 'string', \Illuminate\Validation\Rule::in(['Million Team', '3AQRAB', 'Mega Team'])],
            // Payment transfer screenshot (stored privately, reviewed by admins).
            'transfer_screenshot' => [$requireProof ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:500'],
            'consent' => ['accepted'],
            // Honeypot: real users never see or fill this field.
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.regex' => __('Please use letters only in your name.'),
            'team.required' => __('Please select your team.'),
            'team.in' => __('Please select a valid team.'),
            'city.required' => __('Please enter your city.'),
            'consent.accepted' => __('Please confirm that your details are correct.'),
            'transfer_screenshot.required' => __('Please upload a screenshot of your transfer.'),
            'transfer_screenshot.mimes' => __('The screenshot must be an image (JPG, PNG or WebP).'),
            'transfer_screenshot.max' => __('The screenshot must be smaller than 10 MB.'),
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => __('full name'),
            'phone' => __('phone number'),
            'email' => __('email'),
            'city' => __('city'),
            'team' => __('team'),
            'transfer_screenshot' => __('transfer screenshot'),
        ];
    }
}
