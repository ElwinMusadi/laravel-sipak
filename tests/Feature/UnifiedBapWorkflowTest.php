<?php

use App\BapCancellationReason;
use App\BapStatus;
use App\Models\Bap;
use App\Models\BapCancellation;
use App\Models\Loket;
use App\Models\SkpdAllocation;
use App\Models\SkpdBox;
use App\Models\User;
use App\SkpdAllocationStatus;
use App\UserRole;

// ──────────────────────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────────────────────

function unifiedPetugas(Loket $loket): User
{
    return User::factory()->create([
        'role' => UserRole::PetugasLoket,
        'loket_id' => $loket->id,
    ]);
}

function unifiedAcceptedAllocation(
    User $petugas,
    Loket $loket,
    int $numeratorStart = 30_001,
    int $numeratorEnd = 30_821,
): SkpdAllocation {
    $box = SkpdBox::factory()->create([
        'box_number' => 'BOX-UNIFIED-'.fake()->unique()->bothify('####??'),
        'numerator_start' => $numeratorStart,
        'numerator_end' => $numeratorEnd,
        'total_sets' => $numeratorEnd - $numeratorStart + 1,
    ]);

    return SkpdAllocation::factory()->create([
        'skpd_box_id' => $box->id,
        'loket_id' => $loket->id,
        'numerator_start' => $numeratorStart,
        'numerator_end' => $numeratorEnd,
        'quantity' => $numeratorEnd - $numeratorStart + 1,
        'status' => SkpdAllocationStatus::Accepted,
        'accepted_by' => $petugas->id,
        'accepted_at' => now(),
    ]);
}

/**
 * @param  list<array{numerator: string, reason: string, description?: string}>  $cancellations
 * @return array<string, mixed>
 */
function unifiedBapPayload(
    int $numeratorStart = 30_001,
    int $numeratorEnd = 30_821,
    int $onlineUsageCount = 10,
    int $cancellationCount = 0,
    array $cancellations = [],
): array {
    return [
        'service_date' => now()->toDateString(),
        'numerator_start' => str_pad((string) $numeratorStart, 7, '0', STR_PAD_LEFT),
        'numerator_end' => str_pad((string) $numeratorEnd, 7, '0', STR_PAD_LEFT),
        'online_usage_count' => $onlineUsageCount,
        'cancellation_count' => $cancellationCount,
        'cancellations' => array_map(fn (array $item): array => [
            'numerator' => $item['numerator'],
            'reason' => $item['reason'],
            'description' => $item['description'] ?? '',
        ], $cancellations),
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// Tests — Unified BAP create with cancellations
// ──────────────────────────────────────────────────────────────────────────────

test('BAP without cancellations stores no child rows and total is derived from range', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(
            numeratorStart: 30_001,
            numeratorEnd: 30_821,
            onlineUsageCount: 10,
            cancellationCount: 0,
        ))
        ->assertRedirect();

    $bap = Bap::query()->sole();

    expect($bap->total_usage)->toBe(821)
        ->and($bap->online_usage_count)->toBe(10)
        ->and($bap->status)->toBe(BapStatus::Draft);

    $this->assertDatabaseCount('bap_cancellations', 0);
});

test('BAP with one cancellation creates one child row atomically with audit', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
            ],
        ))
        ->assertRedirect();

    $bap = Bap::query()->sole();
    $cancellation = BapCancellation::query()->sole();

    expect($cancellation->bap_id)->toBe($bap->id)
        ->and($cancellation->numerator)->toBe(30_010)
        ->and($cancellation->reason)->toBe(BapCancellationReason::PrinterError);

    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Bap::class,
        'auditable_id' => $bap->id,
        'event' => 'bap_cancellation.recorded',
    ]);
});

test('BAP with multiple cancellations stores all child rows', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 2,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
                ['numerator' => '0030075', 'reason' => BapCancellationReason::NetworkError->value],
            ],
        ))
        ->assertRedirect();

    $bap = Bap::query()->sole();

    expect($bap->cancellations()->count())->toBe(2);
    $this->assertDatabaseHas('bap_cancellations', ['bap_id' => $bap->id, 'numerator' => 30_010]);
    $this->assertDatabaseHas('bap_cancellations', ['bap_id' => $bap->id, 'numerator' => 30_075]);
});

test('store rejects when cancellation_count does not match detail row count', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->from(route('baps.create'))
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 2,  // claims 2
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
                // only 1 detail supplied
            ],
        ))
        ->assertRedirect(route('baps.create'))
        ->assertSessionHasErrors('cancellation_count');

    $this->assertDatabaseCount('baps', 0);
});

test('store rejects a cancellation numerator outside the BAP range', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->from(route('baps.create'))
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0029999', 'reason' => BapCancellationReason::PrinterError->value],
            ],
        ))
        ->assertRedirect(route('baps.create'))
        ->assertSessionHasErrors();

    $this->assertDatabaseCount('baps', 0);
});

test('store rejects a duplicate cancellation numerator in the same payload', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->from(route('baps.create'))
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 2,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
                ['numerator' => '0030010', 'reason' => BapCancellationReason::NetworkError->value],
            ],
        ))
        ->assertRedirect(route('baps.create'))
        ->assertSessionHasErrors();

    $this->assertDatabaseCount('baps', 0);
});

test('store rejects Isi Sendiri reason without a description', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->from(route('baps.create'))
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::Custom->value, 'description' => ''],
            ],
        ))
        ->assertRedirect(route('baps.create'))
        ->assertSessionHasErrors();

    $this->assertDatabaseCount('baps', 0);
});

test('Isi Sendiri reason with description is accepted', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::Custom->value, 'description' => 'Kertas tertindih proses lainnya.'],
            ],
        ))
        ->assertRedirect();

    $bap = Bap::query()->sole();
    $cancellation = BapCancellation::query()->sole();

    expect($cancellation->reason)->toBe(BapCancellationReason::Custom)
        ->and($cancellation->description)->toBe('Kertas tertindih proses lainnya.');
    expect($bap->total_usage)->toBe(821);
});

test('total pemakaian is not reduced by cancellation count', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(
            numeratorStart: 30_001,
            numeratorEnd: 30_021,
            onlineUsageCount: 3,
            cancellationCount: 2,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
                ['numerator' => '0030011', 'reason' => BapCancellationReason::NetworkError->value],
            ],
        ))
        ->assertRedirect();

    $bap = Bap::query()->sole();

    // total = end - start + 1 = 21, not 21 - 3 - 2 = 16
    expect($bap->total_usage)->toBe(21)
        ->and($bap->online_usage_count)->toBe(3)
        ->and($bap->cancellations()->count())->toBe(2);
});

// ──────────────────────────────────────────────────────────────────────────────
// Tests — draft edit (0→1, 1→2, 2→1, update details)
// ──────────────────────────────────────────────────────────────────────────────

test('draft edit adds cancellations when count goes from 0 to 1', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(cancellationCount: 0))
        ->assertRedirect();

    $bap = Bap::query()->sole();

    $this->actingAs($petugas)
        ->put(route('baps.update', $bap), unifiedBapPayload(
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
            ],
        ))
        ->assertRedirect(route('baps.show', $bap));

    expect($bap->cancellations()->count())->toBe(1);
});

test('draft edit removes a cancellation when count goes from 2 to 1', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(
            cancellationCount: 2,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
                ['numerator' => '0030011', 'reason' => BapCancellationReason::NetworkError->value],
            ],
        ))
        ->assertRedirect();

    $bap = Bap::query()->sole();

    // Now reduce to 1 — 0030011 should be removed
    $this->actingAs($petugas)
        ->put(route('baps.update', $bap), unifiedBapPayload(
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
            ],
        ))
        ->assertRedirect(route('baps.show', $bap));

    expect($bap->cancellations()->count())->toBe(1);
    $this->assertDatabaseMissing('bap_cancellations', ['bap_id' => $bap->id, 'numerator' => 30_011]);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Bap::class,
        'auditable_id' => $bap->id,
        'event' => 'bap_cancellation.removed',
    ]);
});

test('draft update rejects range narrowed to exclude existing cancellation in payload', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket, 30_001, 30_100);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(
            numeratorStart: 30_001,
            numeratorEnd: 30_100,
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0030080', 'reason' => BapCancellationReason::PrinterError->value],
            ],
        ))
        ->assertRedirect();

    $bap = Bap::query()->sole();

    // Try to update with narrowed range that excludes 30080
    $this->actingAs($petugas)
        ->from(route('baps.edit', $bap))
        ->put(route('baps.update', $bap), [
            'service_date' => now()->toDateString(),
            'numerator_start' => '0030001',
            'numerator_end' => '0030050',  // 30080 is outside
            'online_usage_count' => 0,
            'cancellation_count' => 1,
            'cancellations' => [
                ['numerator' => '0030080', 'reason' => BapCancellationReason::PrinterError->value, 'description' => ''],
            ],
        ])
        ->assertRedirect(route('baps.edit', $bap))
        ->assertSessionHasErrors();
});

test('submitted BAP cannot have cancellations mutated', function () {
    $loket = Loket::factory()->create();
    $petugas = unifiedPetugas($loket);
    unifiedAcceptedAllocation($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.store'), unifiedBapPayload(cancellationCount: 0))
        ->assertRedirect();

    $bap = Bap::query()->sole();

    $this->actingAs($petugas)
        ->post(route('baps.submit', $bap))
        ->assertRedirect();

    expect($bap->refresh()->status)->toBe(BapStatus::Submitted);

    $this->actingAs($petugas)
        ->put(route('baps.update', $bap), unifiedBapPayload(
            cancellationCount: 1,
            cancellations: [
                ['numerator' => '0030010', 'reason' => BapCancellationReason::PrinterError->value],
            ],
        ))
        ->assertForbidden();

    $this->assertDatabaseCount('bap_cancellations', 0);
});
