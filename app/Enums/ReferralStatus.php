<?php

namespace App\Enums;

enum ReferralStatus: string
{
    case Received = 'received';
    case Triaging = 'triaging';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isCancellable(): bool
    {
        return in_array($this, [
            self::Received,
            self::Triaging,
        ]);
    }

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::Triaging => 'Triaging',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }
}
