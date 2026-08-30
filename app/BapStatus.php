<?php

namespace App;

enum BapStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case WaitingVerification = 'waiting_verification';

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => $status === self::Submitted,
            self::Submitted => $status === self::WaitingVerification,
            self::WaitingVerification => false,
        };
    }
}
