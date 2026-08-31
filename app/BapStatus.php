<?php

namespace App;

enum BapStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderVerification = 'under_verification';
    case NeedsClarification = 'needs_clarification';
    case WaitingVerificationPhase2 = 'waiting_verification_phase_2';

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => $status === self::Submitted,
            self::Submitted => $status === self::UnderVerification,
            self::UnderVerification => in_array($status, [
                self::NeedsClarification,
                self::WaitingVerificationPhase2,
            ], true),
            self::NeedsClarification, self::WaitingVerificationPhase2 => false,
        };
    }
}
