<?php

namespace App\Models;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Referral extends Model
{
    use HasFactory, HasUuids, LogsActivity;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'priority',
                'cancelled_at',
                'cancellation_reason',
                'triaged_at',
            ])
            ->logOnlyDirty()
            ->useLogName('referral')
            ->setDescriptionForEvent(function (string $eventName): string {
                return match ($eventName) {
                    'created' => __('referral.audit.created'),
                    'updated' => $this->getUpdateDescription(),
                    'deleted' => __('referral.audit.deleted'),
                    default => "Referral {$eventName}",
                };
            });
    }

    private function getUpdateDescription(): string
    {
        if ($this->wasChanged('status')) {
            return match ($this->status) {
                ReferralStatus::Triaging => __('referral.audit.triage_started'),
                ReferralStatus::Accepted => __('referral.audit.triage_accepted'),
                ReferralStatus::Rejected => __('referral.audit.triage_rejected'),
                ReferralStatus::Cancelled => __('referral.audit.cancelled'),
                default => __('referral.audit.status_changed', ['status' => $this->status->label()]),
            };
        }

        return __('referral.audit.updated');
    }
}
