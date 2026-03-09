<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient' => [
                'first_name' => $this->patient_first_name,
                'last_name' => $this->patient_last_name,
                'date_of_birth' => $this->patient_date_of_birth->format('Y-m-d'),
                'phone' => $this->patient_phone,
                'email' => $this->patient_email,
            ],
            'reason' => $this->reason,
            'priority' => $this->priority?->value,
            'status' => $this->status?->value,
            'source_system' => $this->source_system,
            'referring_provider' => $this->referring_provider,
            'notes' => $this->notes,
            'triaged_at' => $this->triaged_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
