<?php

namespace App\Services;

use App\Enums\ReferralStatus;
use App\Events\ReferralCreated;
use App\Models\Referral;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    public function create(array $data): Referral
    {
        $referral = Referral::create($data);

        ReferralCreated::dispatch($referral);

        return $referral;
    }

    public function find(string $id): Referral
    {
        return Referral::findOrFail($id);
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Referral::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('patient_first_name', 'like', "%{$search}%")
                  ->orWhere('patient_last_name', 'like', "%{$search}%")
                  ->orWhere('source_system', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    public function cancel(Referral $referral, ?string $reason = null): Referral
    {
        if (! $referral->isCancellable()) {
            abort(422, __('referral.messages.cannot_cancel', [
                'status' => $referral->status->label(),
            ]));
        }

        $referral->update([
            'status' => ReferralStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $referral->fresh();
    }
}
