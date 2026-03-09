<?php

namespace App\Models;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_first_name',
        'patient_last_name',
        'patient_date_of_birth',
        'patient_phone',
        'patient_email',
        'reason',
        'priority',
        'status',
        'source_system',
        'referring_provider',
        'notes',
        'triaged_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'priority' => ReferralPriority::class,
            'patient_date_of_birth' => 'date',
            'triaged_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function isCancellable(): bool
    {
        return $this->status->isCancellable();
    }
}
