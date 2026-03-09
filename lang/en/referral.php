<?php

return [
    'validation' => [
        'patient_first_name.required' => 'Patient first name is required.',
        'patient_first_name.max' => 'Patient first name must not exceed 255 characters.',
        'patient_last_name.required' => 'Patient last name is required.',
        'patient_last_name.max' => 'Patient last name must not exceed 255 characters.',
        'patient_date_of_birth.required' => 'Patient date of birth is required.',
        'patient_date_of_birth.date' => 'Patient date of birth must be a valid date.',
        'patient_date_of_birth.before' => 'Patient date of birth must be a date before today.',
        'patient_email.email' => 'Patient email must be a valid email address.',
        'reason.required' => 'Referral reason is required.',
        'reason.max' => 'Referral reason must not exceed 2000 characters.',
        'priority.Illuminate\Validation\Rules\Enum' => 'Priority must be one of: urgent, high, normal, low.',
        'source_system.required' => 'Source system is required.',
        'source_system.max' => 'Source system must not exceed 255 characters.',
        'notes.max' => 'Notes must not exceed 5000 characters.',
    ],

    'messages' => [
        'created' => 'Referral has been successfully created.',
        'cancelled' => 'Referral has been successfully cancelled.',
        'cannot_cancel' => 'Referral cannot be cancelled because it is currently :status.',
        'not_found' => 'Referral not found.',
    ],
];
