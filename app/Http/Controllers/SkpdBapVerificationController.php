<?php

namespace App\Http\Controllers;

use App\Actions\SkpdVerification\CompleteBapVerification;
use App\Actions\SkpdVerification\StartBapVerification;
use App\BapCancellationReason;
use App\BapStatus;
use App\BapVerificationChecklistType;
use App\BapVerificationResult;
use App\BapVerificationStage;
use App\BapVerificationStatus;
use App\Http\Requests\SkpdVerification\CompleteBapVerificationRequest;
use App\Models\Bap;
use App\Models\BapVerification;
use App\Models\BapVerificationChecklistItem;
use App\Models\BapVerificationDiscrepancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SkpdBapVerificationController extends Controller
{
    /**
     * Display the queue of BAP records ready for Phase 1 verification.
     */
    public function index(Request $request): Response
    {
        $this->actor($request);

        Gate::authorize('view-bap-verifications-phase-1');

        $baps = Bap::query()
            ->with([
                'loket:id,name',
                'creator:id,name',
                'verifications' => function (Relation $query): void {
                    $query
                        ->where('stage', BapVerificationStage::Phase1->value)
                        ->with('verifier:id,name')
                        ->orderByDesc('attempt');
                },
            ])
            ->whereIn('status', [
                BapStatus::Submitted->value,
                BapStatus::UnderVerification->value,
            ])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function (Bap $bap): array {
                $verification = $bap->verifications->first();

                return [
                    'id' => $bap->id,
                    'service_date' => $bap->service_date->toDateString(),
                    'loket' => $bap->loket->name,
                    'numerator_start' => $bap->numerator_start,
                    'numerator_end' => $bap->numerator_end,
                    'total_usage' => $bap->total_usage,
                    'online_usage_count' => $bap->online_usage_count,
                    'status' => $bap->status->value,
                    'created_by' => $bap->creator->name,
                    'submitted_at' => $bap->submitted_at?->toIso8601String(),
                    'verification' => $verification === null ? null : [
                        'verifier' => $verification->verifier->name,
                        'started_at' => $verification->started_at->toIso8601String(),
                    ],
                ];
            });

        return Inertia::render('bap-verifications/index', [
            'baps' => $baps,
        ]);
    }

    /**
     * Show one BAP with its immutable source data and Phase 1 verification state.
     */
    public function show(Bap $bap, Request $request): Response
    {
        $actor = $this->actor($request);

        Gate::authorize('view-bap-verifications-phase-1');

        $bap->load([
            'loket:id,name',
            'creator:id,name',
            'usageSegments' => function (Relation $query): void {
                $query
                    ->with('skpdAllocation.skpdBox:id,box_number')
                    ->orderBy('numerator_start');
            },
            'cancellations' => function (Relation $query): void {
                $query
                    ->with('creator:id,name')
                    ->orderBy('numerator');
            },
        ]);
        $verification = $this->phaseOneVerification($bap);

        return Inertia::render('bap-verifications/show', [
            'bap' => [
                'id' => $bap->id,
                'service_date' => $bap->service_date->toDateString(),
                'loket' => $bap->loket->name,
                'created_by' => $bap->creator->name,
                'submitted_at' => $bap->submitted_at?->toIso8601String(),
                'status' => $bap->status->value,
                'numerator_start' => $bap->numerator_start,
                'numerator_end' => $bap->numerator_end,
                'total_usage' => $bap->total_usage,
                'online_usage_count' => $bap->online_usage_count,
                'segments' => $bap->usageSegments
                    ->map(fn ($segment): array => [
                        'id' => $segment->id,
                        'allocation_id' => $segment->skpd_allocation_id,
                        'box_number' => $segment->skpdAllocation->skpdBox->box_number,
                        'numerator_start' => $segment->numerator_start,
                        'numerator_end' => $segment->numerator_end,
                        'quantity' => $segment->quantity,
                    ])
                    ->values()
                    ->all(),
                'cancellations' => $bap->cancellations
                    ->map(fn ($cancellation): array => [
                        'id' => $cancellation->id,
                        'numerator' => $cancellation->numerator,
                        'reason' => $cancellation->reason->value,
                        'reason_label' => match ($cancellation->reason) {
                            BapCancellationReason::Cancelled => 'Batal',
                            BapCancellationReason::Damaged => 'Rusak',
                        },
                        'description' => $cancellation->description,
                        'created_by' => $cancellation->creator->name,
                    ])
                    ->values()
                    ->all(),
            ],
            'verification' => $this->verificationData($verification),
            'checklist' => $this->checklistData($bap, $verification),
            'can' => [
                'start' => $actor->can('start-bap-verification-phase-1', $bap),
                'complete' => $actor->can('complete-bap-verification-phase-1')
                    && $verification?->status === BapVerificationStatus::InProgress
                    && $verification->verifier_id === $actor->id,
            ],
        ]);
    }

    /**
     * Claim a submitted BAP for one Petugas Penetapan.
     */
    public function start(Bap $bap, Request $request, StartBapVerification $startVerification): RedirectResponse
    {
        $startVerification->handle($this->actor($request), $bap);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Verifikasi Tahap 1 dimulai. Catat hasil pemeriksaan fisik sebelum menyelesaikan verifikasi.']);

        return to_route('bap-verifications.show', $bap);
    }

    /**
     * Complete the active verification with a passed or discrepancy result.
     */
    public function complete(
        CompleteBapVerificationRequest $request,
        Bap $bap,
        CompleteBapVerification $completeVerification,
    ): RedirectResponse {
        $verification = $completeVerification->handle(
            $this->actor($request),
            $bap,
            $request->verificationAttributes(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $verification->result === BapVerificationResult::Passed
                ? 'Verifikasi Tahap 1 lulus dan BAP masuk antrean Verifikasi Tahap 2.'
                : 'Selisih dicatat dan BAP dikirim ke kebutuhan klarifikasi.',
        ]);

        return to_route('bap-verifications.show', $bap);
    }

    private function phaseOneVerification(Bap $bap): ?BapVerification
    {
        return BapVerification::query()
            ->where('bap_id', $bap->id)
            ->where('stage', BapVerificationStage::Phase1->value)
            ->with([
                'verifier:id,name',
                'checklistItems' => fn (Relation $query): Relation => $query->orderBy('type'),
                'discrepancies' => fn (Relation $query): Relation => $query->orderBy('type'),
                'clarificationRequest',
            ])
            ->orderByDesc('attempt')
            ->first();
    }

    /**
     * @return array{id: int, verifier: string, status: string, result: string|null, notes: string|null, started_at: string, completed_at: string|null, discrepancies: list<array{type: string, label: string, expected_value: string, actual_value: string, difference: int|null, notes: string}>, clarification_requested: bool}|null
     */
    private function verificationData(?BapVerification $verification): ?array
    {
        if ($verification === null) {
            return null;
        }

        return [
            'id' => $verification->id,
            'verifier' => $verification->verifier->name,
            'status' => $verification->status->value,
            'result' => $verification->result?->value,
            'notes' => $verification->notes,
            'started_at' => $verification->started_at->toIso8601String(),
            'completed_at' => $verification->completed_at?->toIso8601String(),
            'discrepancies' => array_values($verification->discrepancies
                ->map(fn (BapVerificationDiscrepancy $discrepancy): array => [
                    'type' => $discrepancy->type->value,
                    'label' => $discrepancy->type->label(),
                    'expected_value' => $discrepancy->expected_value,
                    'actual_value' => $discrepancy->actual_value,
                    'difference' => $discrepancy->difference,
                    'notes' => $discrepancy->notes,
                ])
                ->values()
                ->all()),
            'clarification_requested' => $verification->clarificationRequest !== null,
        ];
    }

    /**
     * @return list<array{type: string, label: string, expected_quantity: int, actual_quantity: int|null, quantity_difference: int|null, expected_numerator_start: int|null, expected_numerator_end: int|null, actual_numerator_start: int|null, actual_numerator_end: int|null, is_attested: bool}>
     */
    private function checklistData(Bap $bap, ?BapVerification $verification): array
    {
        $items = $verification?->checklistItems->keyBy('type') ?? collect();

        return array_map(function (BapVerificationChecklistType $type) use ($bap, $items): array {
            $item = $items->get($type->value);

            if (! $item instanceof BapVerificationChecklistItem) {
                return [
                    'type' => $type->value,
                    'label' => $type->label(),
                    'expected_quantity' => $this->expectedQuantity($bap, $type),
                    'actual_quantity' => null,
                    'quantity_difference' => null,
                    'expected_numerator_start' => $type->usesNumeratorRange() ? $bap->numerator_start : null,
                    'expected_numerator_end' => $type->usesNumeratorRange() ? $bap->numerator_end : null,
                    'actual_numerator_start' => null,
                    'actual_numerator_end' => null,
                    'is_attested' => false,
                ];
            }

            return [
                'type' => $type->value,
                'label' => $type->label(),
                'expected_quantity' => $item->expected_quantity ?? $this->expectedQuantity($bap, $type),
                'actual_quantity' => $item->actual_quantity,
                'quantity_difference' => $item->quantity_difference,
                'expected_numerator_start' => $item->expected_numerator_start,
                'expected_numerator_end' => $item->expected_numerator_end,
                'actual_numerator_start' => $item->actual_numerator_start,
                'actual_numerator_end' => $item->actual_numerator_end,
                'is_attested' => $item->is_attested,
            ];
        }, BapVerificationChecklistType::cases());
    }

    private function expectedQuantity(Bap $bap, BapVerificationChecklistType $type): int
    {
        return match ($type) {
            BapVerificationChecklistType::UsageQuantity,
            BapVerificationChecklistType::Numerator,
            BapVerificationChecklistType::TindisanSets => $bap->total_usage,
            BapVerificationChecklistType::Cancellation => $bap->cancellations->count(),
            BapVerificationChecklistType::Online => $bap->online_usage_count,
        };
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
