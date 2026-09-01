<?php

namespace App\Actions\SkpdVerification;

use App\Actions\SkpdInventory\RecordDomainAudit;
use App\BapClarificationStatus;
use App\BapStatus;
use App\Models\Bap;
use App\Models\BapClarificationRequest;
use App\Models\BapClarificationResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitBapClarificationResponse
{
    public function __construct(private readonly RecordDomainAudit $audit) {}

    public function handle(User $actor, BapClarificationRequest $clarification, string $response): BapClarificationResponse
    {
        return DB::transaction(function () use ($actor, $clarification, $response): BapClarificationResponse {
            $lockedClarification = BapClarificationRequest::query()
                ->with('bap')
                ->lockForUpdate()
                ->findOrFail($clarification->id);
            $lockedBap = Bap::query()->lockForUpdate()->findOrFail($lockedClarification->bap_id);

            if (! $actor->canOperateAtLoket($lockedBap->loket_id)) {
                throw ValidationException::withMessages([
                    'clarification' => 'Hanya Petugas Loket pemilik BAP yang dapat memberikan tanggapan.',
                ]);
            }

            if ($lockedBap->status !== BapStatus::NeedsClarification || ! $lockedClarification->status->canReceiveResponse()) {
                throw ValidationException::withMessages([
                    'status' => 'Klarifikasi ini sudah tidak berada pada state yang dapat ditanggapi.',
                ]);
            }

            $latestRound = BapClarificationResponse::query()
                ->where('bap_clarification_request_id', $lockedClarification->id)
                ->lockForUpdate()
                ->max('round');
            $submittedAt = now();
            $clarificationResponse = BapClarificationResponse::create([
                'bap_clarification_request_id' => $lockedClarification->id,
                'round' => ($latestRound ?? 0) + 1,
                'responded_by' => $actor->id,
                'response' => trim($response),
                'responded_at' => $submittedAt,
            ]);

            $previousStatus = $lockedClarification->status;
            $lockedClarification->status = BapClarificationStatus::Responded;
            $lockedClarification->save();

            $this->audit->handle($actor, $lockedBap, 'bap_clarification.response_submitted', [
                'clarification_id' => $lockedClarification->id,
                'status' => $previousStatus->value,
            ], [
                'clarification_id' => $lockedClarification->id,
                'response_id' => $clarificationResponse->id,
                'round' => $clarificationResponse->round,
                'status' => $lockedClarification->status->value,
                'responded_at' => $submittedAt->toISOString(),
            ]);

            return $clarificationResponse;
        }, attempts: 3);
    }
}
