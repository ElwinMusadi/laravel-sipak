<?php

namespace App;

enum BapVerificationStage: string
{
    case Phase1 = 'phase_1';
    case Phase2 = 'phase_2';

    public function label(): string
    {
        return match ($this) {
            self::Phase1 => 'Verifikasi Tahap 1',
            self::Phase2 => 'Verifikasi Tahap 2',
        };
    }

    public function verifierRole(): UserRole
    {
        return match ($this) {
            self::Phase1 => UserRole::PetugasPenetapan,
            self::Phase2 => UserRole::PetugasVerifikasi,
        };
    }

    /**
     * @return list<string>
     */
    public function queueBapStatuses(): array
    {
        return match ($this) {
            self::Phase1 => [
                BapStatus::Submitted->value,
                BapStatus::UnderVerification->value,
            ],
            self::Phase2 => [
                BapStatus::WaitingVerificationPhase2->value,
                BapStatus::UnderVerificationPhase2->value,
            ],
        };
    }

    public function startBapStatus(): BapStatus
    {
        return match ($this) {
            self::Phase1 => BapStatus::Submitted,
            self::Phase2 => BapStatus::WaitingVerificationPhase2,
        };
    }

    public function inProgressBapStatus(): BapStatus
    {
        return match ($this) {
            self::Phase1 => BapStatus::UnderVerification,
            self::Phase2 => BapStatus::UnderVerificationPhase2,
        };
    }

    public function passedBapStatus(): BapStatus
    {
        return match ($this) {
            self::Phase1 => BapStatus::WaitingVerificationPhase2,
            self::Phase2 => BapStatus::VerifiedPhase2,
        };
    }

    public function auditPrefix(): string
    {
        return 'bap_verification.'.$this->value;
    }
}
