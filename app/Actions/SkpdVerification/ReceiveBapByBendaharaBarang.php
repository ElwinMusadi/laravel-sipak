<?php

namespace App\Actions\SkpdVerification;

use App\Actions\SkpdInventory\RecordDomainAudit;
use App\BapClarificationStatus;
use App\BapStatus;
use App\BapVerificationResult;
use App\BapVerificationStage;
use App\BapVerificationStatus;
use App\Models\Bap;
use App\Models\BapClarificationRequest;
use App\Models\BapVerification;
use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveBapByBendaharaBarang
{
    public function __construct(private readonly RecordDomainAudit $audit) {}

    public function handle(User $actor, Bap $bap, ?string $receiptNotes = null): Bap
    {
        return DB::transaction(function () use ($actor, $bap, $receiptNotes): Bap {
            $lockedBap = Bap::query()->lockForUpdate()->findOrFail($bap->id);

            if ($actor->role !== UserRole::BendaharaBarang) {
                throw ValidationException::withMessages([
                    'bap' => 'Hanya Bendahara Barang yang dapat menerima BAP secara administratif.',
                ]);
            }

            if ($lockedBap->status !== BapStatus::VerifiedPhase2) {
                throw ValidationException::withMessages([
                    'status' => 'BAP hanya dapat diterima setelah lulus Verifikasi Tahap 2 dan belum selesai administratif.',
                ]);
            }

            $this->ensurePassedVerification($lockedBap, BapVerificationStage::Phase1);
            $this->ensurePassedVerification($lockedBap, BapVerificationStage::Phase2);

            if (BapVerification::query()
                ->where('bap_id', $lockedBap->id)
                ->where('status', BapVerificationStatus::InProgress)
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'BAP tidak dapat diterima ketika masih ada verifikasi yang berlangsung.',
                ]);
            }

            if (BapClarificationRequest::query()
                ->where('bap_id', $lockedBap->id)
                ->whereIn('status', [
                    BapClarificationStatus::WaitingResponse,
                    BapClarificationStatus::Responded,
                    BapClarificationStatus::Reopened,
                ])
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'BAP tidak dapat diterima sebelum seluruh klarifikasi selesai.',
                ]);
            }

            $receivedAt = now();
            $lockedBap->transitionTo(BapStatus::Completed);
            $lockedBap->received_by = $actor->id;
            $lockedBap->received_at = $receivedAt;
            $lockedBap->receipt_notes = filled($receiptNotes) ? trim($receiptNotes) : null;
            $lockedBap->save();

            $this->audit->handle($actor, $lockedBap, 'bap_administration.received', [
                'status' => BapStatus::VerifiedPhase2->value,
            ], [
                'status' => $lockedBap->status->value,
                'received_by' => $lockedBap->received_by,
                'received_at' => $receivedAt->toISOString(),
                'receipt_notes' => $lockedBap->receipt_notes,
            ]);

            return $lockedBap;
        }, attempts: 3);
    }

    private function ensurePassedVerification(Bap $bap, BapVerificationStage $stage): void
    {
        $hasPassedVerification = BapVerification::query()
            ->where('bap_id', $bap->id)
            ->where('stage', $stage)
            ->where('status', BapVerificationStatus::Completed)
            ->where('result', BapVerificationResult::Passed)
            ->lockForUpdate()
            ->exists();

        if (! $hasPassedVerification) {
            throw ValidationException::withMessages([
                'bap' => "BAP belum memiliki hasil lulus {$stage->label()} yang lengkap.",
            ]);
        }
    }
}
