<?php

namespace App\Actions\SkpdVerification;

use App\Actions\SkpdInventory\RecordDomainAudit;
use App\BapStatus;
use App\BapVerificationStage;
use App\BapVerificationStatus;
use App\Models\Bap;
use App\Models\BapVerification;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartBapVerification
{
    public function __construct(private readonly RecordDomainAudit $audit) {}

    public function handle(User $actor, Bap $bap): BapVerification
    {
        return DB::transaction(function () use ($actor, $bap): BapVerification {
            $lockedBap = Bap::query()->lockForUpdate()->findOrFail($bap->id);

            if ($actor->role !== UserRole::PetugasPenetapan) {
                throw ValidationException::withMessages([
                    'bap' => 'Verifikasi Tahap 1 hanya dapat dimulai oleh Petugas Penetapan.',
                ]);
            }

            if ($lockedBap->status !== BapStatus::Submitted) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya BAP yang telah diajukan dan belum diverifikasi dapat dimulai pemeriksaannya.',
                ]);
            }

            $verification = BapVerification::create([
                'bap_id' => $lockedBap->id,
                'verifier_id' => $actor->id,
                'stage' => BapVerificationStage::Phase1,
                'attempt' => 1,
                'status' => BapVerificationStatus::InProgress,
                'started_at' => now(),
            ]);

            $lockedBap->transitionTo(BapStatus::UnderVerification);
            $lockedBap->save();

            $this->audit->handle($actor, $lockedBap, 'bap_verification.phase_1_started', [
                'status' => BapStatus::Submitted->value,
            ], [
                'verification_id' => $verification->id,
                'stage' => $verification->stage->value,
                'status' => $lockedBap->status->value,
                'started_at' => $verification->started_at->toISOString(),
            ]);

            return $verification;
        }, attempts: 3);
    }
}
