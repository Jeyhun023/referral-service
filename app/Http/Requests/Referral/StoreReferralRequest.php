<?php

namespace App\Http\Requests\Referral;

use App\Enums\ReferralPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_first_name' => ['required', 'string', 'max:255'],
            'patient_last_name' => ['required', 'string', 'max:255'],
            'patient_date_of_birth' => ['required', 'date', 'before:today'],
            'patient_phone' => ['nullable', 'string', 'max:20'],
            'patient_email' => ['nullable', 'email', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'priority' => ['sometimes', Rule::enum(ReferralPriority::class)],
            'source_system' => ['required', 'string', 'max:255'],
            'referring_provider' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return __('referral.validation');
    }
}
