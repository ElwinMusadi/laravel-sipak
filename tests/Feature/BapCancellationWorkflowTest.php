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
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia as Assert;

function cancellationPetugas(Loket $loket, ?string $name = null): User
{
    return User::factory()->create([
        'name' => $name ?? 'Petugas Cancellation',
        'role' => UserRole::PetugasLoket,
        'loket_id' => $loket->id,
    ]);
}

function cancellationAllocation(
    User $petugas,
    Loket $loket,
    int $numeratorStart = 582_608,
    int $numeratorEnd = 582_620,
): SkpdAllocation {
    $box = SkpdBox::factory()->create([
        'box_number' => 'BOX-CANCELLATION-'.fake()->unique()->bothify('####??'),
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
 * @return array{service_date: string, numerator_start: string, numerator_end: string, online_usage_count: int}
 */
function cancellationBapPayload(
    int $numeratorStart = 582_608,
    int $numeratorEnd = 582_620,
): array {
    return [
        'service_date' => now()->toDateString(),
        'numerator_start' => str_pad((string) $numeratorStart, 7, '0', STR_PAD_LEFT),
        'numerator_end' => str_pad((string) $numeratorEnd, 7, '0', STR_PAD_LEFT),
        'online_usage_count' => 0,
    ];
}

/**
 * @return array{bap: Bap, allocation: SkpdAllocation}
 */
function cancellableBap(
    User $petugas,
    Loket $loket,
    int $numeratorStart = 582_608,
    int $numeratorEnd = 582_620,
    int $allocationEnd = 582_620,
): array {
    $allocation = cancellationAllocation(
        $petugas,
        $loket,
        $numeratorStart,
        $allocationEnd,
    );

    test()->actingAs($petugas)
        ->post(
            route('baps.store'),
            cancellationBapPayload($numeratorStart, $numeratorEnd),
        )
        ->assertRedirect();

    return [
        'bap' => Bap::query()->sole(),
        'allocation' => $allocation,
    ];
}

/**
 * @return array{numerator: string, reason: string, description: string}
 */
function cancellationPayload(
    string $numerator = '0582612',
    BapCancellationReason $reason = BapCancellationReason::Damaged,
    string $description = 'Cetakan tidak dapat digunakan.',
): array {
    return [
        'numerator' => $numerator,
        'reason' => $reason->value,
        'description' => $description,
    ];
}

test('Petugas Loket records an individual cancellation without changing BAP usage or allocation ledger', function () {
    $loket = Loket::factory()->create(['name' => 'Loket Cancellation']);
    $petugas = cancellationPetugas($loket);
    ['bap' => $bap, 'allocation' => $allocation] = cancellableBap(
        $petugas,
        $loket,
        numeratorEnd: 582_615,
    );

    $this->actingAs($petugas)
        ->post(
            route('baps.cancellations.store', $bap),
            cancellationPayload('582612'),
        )
        ->assertRedirect();

    $cancellation = BapCancellation::query()->sole();

    $this->assertDatabaseHas('bap_cancellations', [
        'id' => $cancellation->id,
        'bap_id' => $bap->id,
        'numerator' => 582_612,
        'reason' => BapCancellationReason::Damaged->value,
        'created_by' => $petugas->id,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'auditable_type' => Bap::class,
        'auditable_id' => $bap->id,
        'event' => 'bap_cancellation.recorded',
    ]);

    expect($bap->refresh()->total_usage)->toBe(8)
        ->and($bap->normalUsageQuantity())->toBe(7)
        ->and($allocation->refresh()->status)->toBe(SkpdAllocationStatus::Accepted)
        ->and((int) $allocation->usageSegments()->sum('quantity'))->toBe(8);

    $this->actingAs($petugas)
        ->get(route('baps.show', $bap))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('baps/show')
            ->where('bap.total_usage', 8)
            ->where('bap.cancellations.quantity', 1)
            ->where('bap.cancellations.normal_usage_quantity', 7)
            ->where('bap.cancellations.items.0.numerator', 582_612)
            ->etc(),
        );
});

test('multiple cancellations are listed individually with the correct BAP summary', function () {
    $loket = Loket::factory()->create();
    $petugas = cancellationPetugas($loket);
    ['bap' => $bap] = cancellableBap($petugas, $loket);

    foreach ([
        ['0582610', BapCancellationReason::Cancelled],
        ['0582612', BapCancellationReason::Damaged],
        ['0582617', BapCancellationReason::Cancelled],
    ] as [$numerator, $reason]) {
        $this->actingAs($petugas)
            ->post(
                route('baps.cancellations.store', $bap),
                cancellationPayload($numerator, $reason),
            )
            ->assertRedirect();
    }

    $this->assertDatabaseCount('bap_cancellations', 3);

    $this->actingAs($petugas)
        ->get(route('bap-cancellations.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bap-cancellations/index')
            ->has('cancellations.data', 3)
            ->where('cancellations.data.0.bap_id', $bap->id)
            ->etc(),
        );

    $cancellation = BapCancellation::query()->where('numerator', 582_612)->sole();

    $this->actingAs($petugas)
        ->get(route('bap-cancellations.show', $cancellation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('bap-cancellations/show')
            ->where('cancellation.numerator', 582_612)
            ->where('cancellation.bap.total_usage', 13)
            ->where('cancellation.bap.cancellation_quantity', 3)
            ->where('cancellation.bap.normal_usage_quantity', 10)
            ->etc(),
        );
});

test('cancellation rejects a numerator that is outside BAP usage and an invalid numerator format', function () {
    $loket = Loket::factory()->create();
    $petugas = cancellationPetugas($loket);
    ['bap' => $bap] = cancellableBap(
        $petugas,
        $loket,
        numeratorEnd: 582_620,
        allocationEnd: 582_630,
    );

    $this->actingAs($petugas)
        ->from(route('baps.cancellations.create', $bap))
        ->post(
            route('baps.cancellations.store', $bap),
            cancellationPayload('0582630'),
        )
        ->assertRedirect(route('baps.cancellations.create', $bap))
        ->assertSessionHasErrors([
            'numerator' => 'Nomeratur tersebut belum tercatat sebagai pemakaian pada BAP ini.',
        ]);

    $this->actingAs($petugas)
        ->from(route('baps.cancellations.create', $bap))
        ->post(
            route('baps.cancellations.store', $bap),
            cancellationPayload('05826123'),
        )
        ->assertRedirect(route('baps.cancellations.create', $bap))
        ->assertSessionHasErrors([
            'numerator' => 'Nomeratur harus berupa angka maksimal tujuh digit.',
        ]);

    $this->assertDatabaseCount('bap_cancellations', 0);
});

test('cancellation rejects a duplicate numerator through the domain action', function () {
    $loket = Loket::factory()->create();
    $petugas = cancellationPetugas($loket);
    ['bap' => $bap] = cancellableBap($petugas, $loket);

    $this->actingAs($petugas)
        ->post(
            route('baps.cancellations.store', $bap),
            cancellationPayload(),
        )
        ->assertRedirect();

    $this->actingAs($petugas)
        ->from(route('baps.cancellations.create', $bap))
        ->post(
            route('baps.cancellations.store', $bap),
            cancellationPayload('0582612', BapCancellationReason::Cancelled),
        )
        ->assertRedirect(route('baps.cancellations.create', $bap))
        ->assertSessionHasErrors([
            'numerator' => 'Nomeratur batal atau rusak sudah pernah dicatat dan tidak dapat digunakan ulang.',
        ]);

    $this->assertDatabaseCount('bap_cancellations', 1);
});

test('cancellation mutation is limited to the BAP creator and cannot be deleted freely', function () {
    $loket = Loket::factory()->create();
    $owner = cancellationPetugas($loket, 'Pembuat BAP');
    $sameLoketUser = cancellationPetugas($loket, 'Petugas Loket Sama');
    $otherLoketUser = cancellationPetugas(Loket::factory()->create(), 'Petugas Loket Lain');
    $bendahara = User::factory()->create(['role' => UserRole::BendaharaBarang]);
    ['bap' => $bap] = cancellableBap($owner, $loket);

    $this->actingAs($sameLoketUser)
        ->post(route('baps.cancellations.store', $bap), cancellationPayload())
        ->assertForbidden();
    $this->actingAs($otherLoketUser)
        ->post(route('baps.cancellations.store', $bap), cancellationPayload())
        ->assertForbidden();
    $this->actingAs($bendahara)
        ->post(route('baps.cancellations.store', $bap), cancellationPayload())
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('baps.cancellations.store', $bap), cancellationPayload())
        ->assertRedirect();

    $cancellation = BapCancellation::query()->sole();

    $this->actingAs($owner)
        ->delete(route('bap-cancellations.show', $cancellation))
        ->assertMethodNotAllowed();

    $this->assertDatabaseCount('bap_cancellations', 1);
});

test('submitted BAP does not accept a new cancellation', function () {
    $loket = Loket::factory()->create();
    $petugas = cancellationPetugas($loket);
    ['bap' => $bap] = cancellableBap($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.submit', $bap))
        ->assertRedirect();

    expect($bap->refresh()->status)->toBe(BapStatus::Submitted);

    $this->actingAs($petugas)
        ->post(route('baps.cancellations.store', $bap), cancellationPayload())
        ->assertForbidden();

    $this->assertDatabaseCount('bap_cancellations', 0);
});

test('database unique constraint prevents a second cancellation for the same numerator', function () {
    $loket = Loket::factory()->create();
    $petugas = cancellationPetugas($loket);
    ['bap' => $bap] = cancellableBap($petugas, $loket);

    $this->actingAs($petugas)
        ->post(route('baps.cancellations.store', $bap), cancellationPayload())
        ->assertRedirect();

    expect(fn () => BapCancellation::create([
        'bap_id' => $bap->id,
        'numerator' => 582_612,
        'reason' => BapCancellationReason::Cancelled,
        'created_by' => $petugas->id,
    ]))->toThrow(QueryException::class);

    $this->assertDatabaseCount('bap_cancellations', 1);
});
