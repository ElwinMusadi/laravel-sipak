<?php

use App\Actions\SkpdVerification\CompleteBapVerification;
use App\Actions\SkpdVerification\StartBapVerification;
use App\BapCancellationReason;
use App\BapClarificationStatus;
use App\BapStatus;
use App\BapVerificationChecklistType;
use App\BapVerificationResult;
use App\BapVerificationStage;
use App\Models\Bap;
use App\Models\BapCancellation;
use App\Models\BapClarificationRequest;
use App\Models\BapUsageSegment;
use App\Models\BapVerification;
use App\Models\Loket;
use App\Models\SkpdAllocation;
use App\Models\SkpdBox;
use App\Models\User;
use App\SkpdAllocationStatus;
use App\UserRole;

/**
 * @return array{bap: Bap, clarification: BapClarificationRequest, loket_user: User, verifier: User, allocation: SkpdAllocation, segment: BapUsageSegment, cancellation: BapCancellation}
 */
function phaseTenDiscrepantClarification(BapVerificationStage $stage): array
{
    $rangeStart = $stage === BapVerificationStage::Phase1 ? 583_000 : 584_000;
    $rangeEnd = $rangeStart + 12;
    $loket = Loket::factory()->create(['name' => "Loket {$stage->value}"]);
    $loketUser = User::factory()->create([
        'role' => UserRole::PetugasLoket,
        'loket_id' => $loket->id,
    ]);
    $box = SkpdBox::factory()->create([
        'box_number' => 'BOX-PHASE-10-'.str()->upper(str()->random(8)),
        'numerator_start' => $rangeStart,
        'numerator_end' => $rangeEnd,
        'total_sets' => 13,
    ]);
    $allocation = SkpdAllocation::factory()->create([
        'skpd_box_id' => $box->id,
        'loket_id' => $loket->id,
        'numerator_start' => $rangeStart,
        'numerator_end' => $rangeEnd,
        'quantity' => 13,
        'status' => SkpdAllocationStatus::Accepted,
        'accepted_by' => $loketUser->id,
        'accepted_at' => now(),
    ]);
    $bap = Bap::factory()->create([
        'loket_id' => $loket->id,
        'service_date' => now()->toDateString(),
        'numerator_start' => $rangeStart,
        'numerator_end' => $rangeEnd,
        'total_usage' => 13,
        'online_usage_count' => 5,
        'status' => $stage->startBapStatus(),
        'created_by' => $loketUser->id,
        'submitted_at' => now()->subHour(),
    ]);
    $segment = BapUsageSegment::create([
        'bap_id' => $bap->id,
        'skpd_allocation_id' => $allocation->id,
        'numerator_start' => $rangeStart,
        'numerator_end' => $rangeEnd,
        'quantity' => 13,
    ]);
    $cancellation = BapCancellation::create([
        'bap_id' => $bap->id,
        'numerator' => $rangeStart + 4,
        'reason' => BapCancellationReason::Damaged,
        'description' => 'Bukti fisik rusak sebelum verifikasi.',
        'created_by' => $loketUser->id,
    ]);

    if ($stage === BapVerificationStage::Phase2) {
        $phaseOneVerifier = User::factory()->create(['role' => UserRole::PetugasPenetapan]);
        BapVerification::factory()
            ->completed(BapVerificationResult::Passed)
            ->create([
                'bap_id' => $bap->id,
                'verifier_id' => $phaseOneVerifier->id,
                'stage' => BapVerificationStage::Phase1,
                'attempt' => 1,
                'started_at' => now()->subMinutes(45),
                'completed_at' => now()->subMinutes(30),
            ]);
    }

    $verifier = User::factory()->create(['role' => $stage->verifierRole()]);
    app(StartBapVerification::class)->handle($verifier, $bap, $stage);
    app(CompleteBapVerification::class)->handle($verifier, $bap, $stage, phaseTenDiscrepancyPayload($bap));

    return [
        'bap' => $bap->refresh(),
        'clarification' => BapClarificationRequest::query()->where('bap_id', $bap->id)->sole(),
        'loket_user' => $loketUser,
        'verifier' => $verifier,
        'allocation' => $allocation,
        'segment' => $segment,
        'cancellation' => $cancellation,
    ];
}

/**
 * @return array{result: string, notes: string, checklist: list<array<string, int|bool|string>>, discrepancies: list<array{type: string, notes: string}>}
 */
function phaseTenDiscrepancyPayload(Bap $bap): array
{
    $payload = phaseTenPassingPayload($bap);
    $payload['result'] = BapVerificationResult::Discrepancy->value;
    $payload['notes'] = 'Mohon Loket melakukan pengecekan ulang terhadap tindisan dan data fisik.';
    $payload['checklist'][4]['actual_quantity'] = 4;
    $payload['discrepancies'] = [[
        'type' => BapVerificationChecklistType::Online->value,
        'notes' => 'Jumlah bukti online fisik kurang satu set dari data sistem.',
    ]];

    return $payload;
}

/**
 * @return array{result: string, notes: string, checklist: list<array<string, int|bool|string>>, discrepancies: list<array{type: string, notes: string}>}
 */
function phaseTenPassingPayload(Bap $bap): array
{
    return [
        'result' => BapVerificationResult::Passed->value,
        'notes' => 'Pemeriksaan ulang sesuai dengan data dan bukti fisik.',
        'checklist' => [
            ['type' => BapVerificationChecklistType::UsageQuantity->value, 'is_attested' => true, 'actual_quantity' => $bap->total_usage],
            ['type' => BapVerificationChecklistType::Numerator->value, 'is_attested' => true, 'actual_numerator_start' => $bap->numerator_start, 'actual_numerator_end' => $bap->numerator_end],
            ['type' => BapVerificationChecklistType::TindisanSets->value, 'is_attested' => true, 'actual_quantity' => $bap->total_usage],
            ['type' => BapVerificationChecklistType::Cancellation->value, 'is_attested' => true, 'actual_quantity' => $bap->cancellations()->count()],
            ['type' => BapVerificationChecklistType::Online->value, 'is_attested' => true, 'actual_quantity' => $bap->online_usage_count],
        ],
        'discrepancies' => [],
    ];
}

function phaseTenRoute(BapVerificationStage $stage, string $action, Bap $bap): string
{
    return match ($stage) {
        BapVerificationStage::Phase1 => route("bap-verifications.{$action}", $bap),
        BapVerificationStage::Phase2 => route("bap-verifications-phase-2.{$action}", $bap),
    };
}

test('a discrepancy from each verification stage creates a Loket clarification ticket', function (BapVerificationStage $stage) {
    $context = phaseTenDiscrepantClarification($stage);

    expect($context['bap']->status)->toBe(BapStatus::NeedsClarification)
        ->and($context['clarification']->status)->toBe(BapClarificationStatus::WaitingResponse)
        ->and($context['clarification']->verification->stage)->toBe($stage);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_id' => $context['bap']->id,
        'event' => 'bap_clarification.requested',
    ]);
})->with([
    'Verifikasi Tahap 1' => BapVerificationStage::Phase1,
    'Verifikasi Tahap 2' => BapVerificationStage::Phase2,
]);

test('a resolved Phase 1 clarification preserves source data and creates a passing second attempt', function () {
    $context = phaseTenDiscrepantClarification(BapVerificationStage::Phase1);
    $bap = $context['bap'];
    $source = $bap->only(['numerator_start', 'numerator_end', 'total_usage', 'online_usage_count']);
    $segmentSource = $context['segment']->only(['skpd_allocation_id', 'numerator_start', 'numerator_end', 'quantity']);
    $allocationSource = $context['allocation']->only(['numerator_start', 'numerator_end', 'quantity', 'status']);
    $cancellationSource = $context['cancellation']->only(['numerator', 'reason', 'description', 'created_by']);

    $this->actingAs($context['loket_user'])
        ->post(route('bap-clarifications.open', $context['clarification']))
        ->assertRedirect(route('bap-clarifications.show', $context['clarification']));
    $this->actingAs($context['loket_user'])
        ->post(route('bap-clarifications.responses.store', $context['clarification']), [
            'response' => 'Bukti online telah ditemukan pada bundel yang terpisah.',
        ])
        ->assertRedirect(route('bap-clarifications.show', $context['clarification']));
    $this->actingAs($context['verifier'])
        ->post(route('bap-clarifications.review', $context['clarification']), [
            'outcome' => 'resolved',
            'notes' => 'Bukti fisik telah diperiksa kembali dan sesuai.',
        ])
        ->assertRedirect(route('bap-clarifications.show', $context['clarification']));

    expect($bap->refresh()->status)->toBe(BapStatus::WaitingReverificationPhase1)
        ->and($context['clarification']->refresh()->status)->toBe(BapClarificationStatus::Resolved);

    $this->actingAs($context['verifier'])
        ->post(phaseTenRoute(BapVerificationStage::Phase1, 'start', $bap))
        ->assertRedirect(phaseTenRoute(BapVerificationStage::Phase1, 'show', $bap));
    $this->actingAs($context['verifier'])
        ->post(phaseTenRoute(BapVerificationStage::Phase1, 'complete', $bap), phaseTenPassingPayload($bap))
        ->assertRedirect(phaseTenRoute(BapVerificationStage::Phase1, 'show', $bap));

    expect($bap->refresh()->status)->toBe(BapStatus::WaitingVerificationPhase2)
        ->and(BapVerification::query()->where('bap_id', $bap->id)->where('stage', BapVerificationStage::Phase1)->count())->toBe(2)
        ->and(BapVerification::query()->where('bap_id', $bap->id)->where('stage', BapVerificationStage::Phase1)->where('attempt', 1)->sole()->result)->toBe(BapVerificationResult::Discrepancy)
        ->and(BapVerification::query()->where('bap_id', $bap->id)->where('stage', BapVerificationStage::Phase1)->where('attempt', 2)->sole()->result)->toBe(BapVerificationResult::Passed)
        ->and($bap->only(['numerator_start', 'numerator_end', 'total_usage', 'online_usage_count']))->toBe($source)
        ->and($context['segment']->refresh()->only(['skpd_allocation_id', 'numerator_start', 'numerator_end', 'quantity']))->toBe($segmentSource)
        ->and($context['allocation']->refresh()->only(['numerator_start', 'numerator_end', 'quantity', 'status']))->toBe($allocationSource)
        ->and($context['cancellation']->refresh()->only(['numerator', 'reason', 'description', 'created_by']))->toBe($cancellationSource);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_id' => $bap->id,
        'event' => 'bap_clarification.reverification_completed',
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_id' => $bap->id,
        'event' => 'bap_clarification.response_submitted',
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_id' => $bap->id,
        'event' => 'bap_clarification.resolved',
    ]);
});

test('a resolved Phase 2 clarification creates a passing second Phase 2 attempt', function () {
    $context = phaseTenDiscrepantClarification(BapVerificationStage::Phase2);
    $bap = $context['bap'];

    $this->actingAs($context['loket_user'])
        ->post(route('bap-clarifications.responses.store', $context['clarification']), ['response' => 'Bukti online ditemukan setelah pengecekan ulang.'])
        ->assertRedirect();
    $this->actingAs($context['verifier'])
        ->post(route('bap-clarifications.review', $context['clarification']), [
            'outcome' => 'resolved',
            'notes' => 'Temuan fisik telah sesuai setelah diperiksa ulang.',
        ])
        ->assertRedirect();

    $this->actingAs($context['verifier'])
        ->post(phaseTenRoute(BapVerificationStage::Phase2, 'start', $bap))
        ->assertRedirect();
    $this->actingAs($context['verifier'])
        ->post(phaseTenRoute(BapVerificationStage::Phase2, 'complete', $bap), phaseTenPassingPayload($bap))
        ->assertRedirect();

    expect($bap->refresh()->status)->toBe(BapStatus::VerifiedPhase2)
        ->and(BapVerification::query()->where('bap_id', $bap->id)->where('stage', BapVerificationStage::Phase2)->count())->toBe(2)
        ->and(BapVerification::query()->where('bap_id', $bap->id)->where('stage', BapVerificationStage::Phase2)->where('attempt', 1)->sole()->result)->toBe(BapVerificationResult::Discrepancy)
        ->and(BapVerification::query()->where('bap_id', $bap->id)->where('stage', BapVerificationStage::Phase2)->where('attempt', 2)->sole()->result)->toBe(BapVerificationResult::Passed);
});

test('a reopened clarification accepts a second Loket response before it is resolved', function () {
    $context = phaseTenDiscrepantClarification(BapVerificationStage::Phase1);

    $this->actingAs($context['loket_user'])
        ->post(route('bap-clarifications.responses.store', $context['clarification']), ['response' => 'Pengecekan awal sudah dilakukan.'])
        ->assertRedirect();
    $this->actingAs($context['verifier'])
        ->post(route('bap-clarifications.review', $context['clarification']), ['outcome' => 'reopened', 'notes' => 'Mohon jelaskan lokasi bundel fisik secara lebih rinci.'])
        ->assertRedirect();
    $this->actingAs($context['loket_user'])
        ->post(route('bap-clarifications.responses.store', $context['clarification']), ['response' => 'Bundel ditemukan pada arsip pengganti dan telah diserahkan untuk diperiksa.'])
        ->assertRedirect();
    $this->actingAs($context['verifier'])
        ->post(route('bap-clarifications.review', $context['clarification']), ['outcome' => 'resolved', 'notes' => 'Lokasi bundel telah diverifikasi dan penyelesaian diterima.'])
        ->assertRedirect();

    expect($context['clarification']->refresh()->status)->toBe(BapClarificationStatus::Resolved)
        ->and($context['bap']->refresh()->status)->toBe(BapStatus::WaitingReverificationPhase1);
    $this->assertDatabaseCount('bap_clarification_responses', 2);
    $this->assertDatabaseCount('bap_clarification_resolutions', 2);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_id' => $context['bap']->id,
        'event' => 'bap_clarification.reopened',
    ]);
});

test('clarification authorization prevents cross-Loket and cross-stage access', function () {
    $phaseOne = phaseTenDiscrepantClarification(BapVerificationStage::Phase1);
    $phaseTwo = phaseTenDiscrepantClarification(BapVerificationStage::Phase2);
    $wrongLoket = User::factory()->create([
        'role' => UserRole::PetugasLoket,
        'loket_id' => Loket::factory()->create()->id,
    ]);
    $phaseOneVerifier = User::factory()->create(['role' => UserRole::PetugasPenetapan]);
    $phaseTwoVerifier = User::factory()->create(['role' => UserRole::PetugasVerifikasi]);

    $this->actingAs($phaseOne['loket_user'])
        ->get(route('bap-clarifications.show', $phaseOne['clarification']))
        ->assertOk();
    $this->actingAs($phaseOne['verifier'])
        ->get(route('bap-clarifications.show', $phaseOne['clarification']))
        ->assertOk();
    $this->actingAs($phaseTwo['verifier'])
        ->get(route('bap-clarifications.show', $phaseTwo['clarification']))
        ->assertOk();
    $this->actingAs($wrongLoket)
        ->get(route('bap-clarifications.show', $phaseOne['clarification']))
        ->assertForbidden();
    $this->actingAs($wrongLoket)
        ->post(route('bap-clarifications.open', $phaseOne['clarification']))
        ->assertForbidden();
    $this->actingAs($phaseOne['loket_user'])
        ->post(route('bap-clarifications.review', $phaseOne['clarification']), ['outcome' => 'resolved', 'notes' => 'Tidak berwenang.'])
        ->assertForbidden();
    $this->actingAs($phaseTwoVerifier)
        ->post(route('bap-clarifications.review', $phaseOne['clarification']), ['outcome' => 'resolved', 'notes' => 'Tahap tidak sesuai.'])
        ->assertForbidden();
    $this->actingAs($phaseOneVerifier)
        ->post(route('bap-clarifications.review', $phaseTwo['clarification']), ['outcome' => 'resolved', 'notes' => 'Tahap tidak sesuai.'])
        ->assertForbidden();
});

test('a second response is rejected after the clarification has entered review', function () {
    $context = phaseTenDiscrepantClarification(BapVerificationStage::Phase1);

    $this->actingAs($context['loket_user'])
        ->post(route('bap-clarifications.responses.store', $context['clarification']), ['response' => 'Tanggapan pertama dari Loket.'])
        ->assertRedirect();
    $this->actingAs($context['loket_user'])
        ->from(route('bap-clarifications.show', $context['clarification']))
        ->post(route('bap-clarifications.responses.store', $context['clarification']), ['response' => 'Tanggapan balapan yang tidak boleh tersimpan.'])
        ->assertRedirect(route('bap-clarifications.show', $context['clarification']))
        ->assertSessionHasErrors('status');

    $this->assertDatabaseCount('bap_clarification_responses', 1);
});
