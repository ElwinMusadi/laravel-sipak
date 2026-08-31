<?php

namespace App\Actions\SkpdVerification;

use App\Actions\SkpdInventory\RecordDomainAudit;
use App\BapVerificationResult;
use App\BapVerificationStage;
use App\BapVerificationStatus;
use App\Models\Bap;
use App\Models\BapVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartBapVerification
{
    public function __construct(private readonly RecordDomainAudit $audit) {}

    public function handle(User $actor, Bap $bap, BapVerificationStage $stage): BapVerification
    {
        return DB::transaction(function () use ($actor, $bap, $stage): BapVerification {
            $lockedBap = Bap::query()->lockForUpdate()->findOrFail($bap->id);

            if ($actor->role !== $stage->verifierRole()) {
                throw ValidationException::withMessages([
                    'bap' => "{$stage->label()} hanya dapat dimulai oleh {$stage->verifierRole()->label()}.",
                ]);
            }

            if ($lockedBap->status !== $stage->startBapStatus()) {
                throw ValidationException::withMessages([
                    'status' => "BAP belum berada pada antrean {$stage->label()}.",
                ]);
            }

            if ($stage === BapVerificationStage::Phase2 && ! BapVerification::query()
                ->where('bap_id', $lockedBap->id)
                ->where('stage', BapVerificationStage::Phase1)
                ->where('status', BapVerificationStatus::Completed)
                ->where('result', BapVerificationResult::Passed)
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'bap' => 'BAP hanya dapat masuk Verifikasi Tahap 2 setelah lulus Verifikasi Tahap 1.',
                ]);
            }

            $verification = BapVerification::create([
                'bap_id' => $lockedBap->id,
                'verifier_id' => $actor->id,
                'stage' => $stage,
                'attempt' => 1,
                'status' => BapVerificationStatus::InProgress,
                'started_at' => now(),
            ]);

            $lockedBap->transitionTo($stage->inProgressBapStatus());
            $lockedBap->save();

            $this->audit->handle($actor, $lockedBap, $stage->auditPrefix().'_started', [
                'status' => $stage->startBapStatus()->value,
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
