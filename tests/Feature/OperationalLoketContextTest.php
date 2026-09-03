<?php

use App\BapStatus;
use App\Models\AuditLog;
use App\Models\Bap;
use App\Models\Loket;
use App\Models\SkpdAllocation;
use App\Models\SkpdBox;
use App\Models\User;
use App\SkpdAllocationStatus;
use App\UserRole;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{service_date: string, numerator_start: string, numerator_end: string, online_usage_count: int, cancellation_count: int}
 */
function operationalContextBapPayload(
    int $numeratorStart,
    int $numeratorEnd,
    int $onlineUsageCount = 0,
): array {
    return [
        'service_date' => now()->toDateString(),
        'numerator_start' => str_pad((string) $numeratorStart, 7, '0', STR_PAD_LEFT),
        'numerator_end' => str_pad((string) $numeratorEnd, 7, '0', STR_PAD_LEFT),
        'online_usage_count' => $onlineUsageCount,
        'cancellation_count' => 0,
    ];
}

function operationalContextAllocation(
    User $actor,
    Loket $loket,
    int $numeratorStart,
    int $numeratorEnd,
): SkpdAllocation {
    $box = SkpdBox::factory()->create([
        'box_number' => "BOX-KONTEKS-{$numeratorStart}",
        'numerator_start' => $numeratorStart,
        'numerator_end' => $numeratorEnd,
        'total_sets' => $numeratorEnd - $numeratorStart + 1,
        'created_by' => $actor->id,
    ]);

    return SkpdAllocation::factory()->create([
        'skpd_box_id' => $box->id,
        'loket_id' => $loket->id,
        'numerator_start' => $numeratorStart,
        'numerator_end' => $numeratorEnd,
        'quantity' => $numeratorEnd - $numeratorStart + 1,
        'status' => SkpdAllocationStatus::Accepted,
        'created_by' => $actor->id,
        'accepted_by' => $actor->id,
        'accepted_at' => now(),
    ]);
}

test('superadmin selects an active Loket context while retaining the Superadmin actor and audit identity', function () {
    $superadmin = User::factory()->create([
        'name' => 'Elwin Bessiesura',
        'role' => UserRole::Superadmin,
        'loket_id' => null,
    ]);
    $mallPelayananPublik = Loket::factory()->create([
        'code' => 'MPP-KONTEKS',
        'name' => 'Mall Pelayanan Publik',
    ]);
    Loket::factory()->create(['is_active' => false]);
    operationalContextAllocation($superadmin, $mallPelayananPublik, 1_000_001, 1_000_010);

    $this->actingAs($superadmin)
        ->get(route('baps.create', ['loket' => $mallPelayananPublik->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('baps/create')
            ->where('loket.id', $mallPelayananPublik->id)
            ->where('loket.name', 'Mall Pelayanan Publik')
            ->has('lokets', 1)
            ->where('lokets.0.id', $mallPelayananPublik->id)
            ->where('expected_numerator_start', 1_000_001)
            ->etc(),
        );

    $this->actingAs($superadmin)
        ->post(route('baps.store'), [
            ...operationalContextBapPayload(1_000_001, 1_000_010, 2),
            'loket_id' => $mallPelayananPublik->id,
        ])
        ->assertRedirect();

    $bap = Bap::query()->sole();
    $audit = AuditLog::query()->where('event', 'bap.created')->sole();

    expect($bap->loket_id)->toBe($mallPelayananPublik->id)
        ->and($bap->created_by)->toBe($superadmin->id)
        ->and($superadmin->refresh()->role)->toBe(UserRole::Superadmin)
        ->and($superadmin->loket_id)->toBeNull()
        ->and($audit->actor_id)->toBe($superadmin->id)
        ->and($audit->new_values)->toMatchArray([
            'loket_id' => $mallPelayananPublik->id,
        ]);

    $this->actingAs($superadmin)
        ->get(route('baps.show', $bap))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('baps/show')
            ->where('bap.loket.name', 'Mall Pelayanan Publik')
            ->where('bap.created_by', 'Elwin Bessiesura')
            ->where('bap.creator_role', 'Superadmin')
            ->etc(),
        );
});

test('Petugas Loket automatically uses its assignment and rejects a tampered Loket context', function () {
    $assignedLoket = Loket::factory()->create(['name' => 'SAMSAT Kantor']);
    $otherLoket = Loket::factory()->create(['name' => 'Mall Pelayanan Publik']);
    $petugasLoket = User::factory()->create([
        'role' => UserRole::PetugasLoket,
        'loket_id' => $assignedLoket->id,
    ]);
    operationalContextAllocation($petugasLoket, $assignedLoket, 2_000_001, 2_000_010);

    $this->actingAs($petugasLoket)
        ->get(route('baps.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('baps/create')
            ->where('loket.id', $assignedLoket->id)
            ->where('loket.name', 'SAMSAT Kantor')
            ->where('lokets', [])
            ->etc(),
        );

    $this->actingAs($petugasLoket)
        ->from(route('baps.create'))
        ->post(route('baps.store'), [
            ...operationalContextBapPayload(2_000_001, 2_000_010),
            'loket_id' => $otherLoket->id,
        ])
        ->assertRedirect(route('baps.create'))
        ->assertSessionHasErrors('loket_id');

    $this->assertDatabaseCount('baps', 0);

    $this->actingAs($petugasLoket)
        ->post(route('baps.store'), operationalContextBapPayload(2_000_001, 2_000_010))
        ->assertRedirect();

    expect(Bap::query()->sole()->loket_id)->toBe($assignedLoket->id);
});

test('inactive Lokets cannot receive new BAPs while their historical BAPs remain readable', function () {
    $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
    $inactiveLoket = Loket::factory()->create(['is_active' => false]);

    $this->actingAs($superadmin)
        ->get(route('baps.create', ['loket' => $inactiveLoket->id]))
        ->assertNotFound();

    $this->actingAs($superadmin)
        ->post(route('baps.store'), [
            ...operationalContextBapPayload(3_000_001, 3_000_010),
            'loket_id' => $inactiveLoket->id,
        ])
        ->assertNotFound();

    $petugasLoket = User::factory()->create([
        'role' => UserRole::PetugasLoket,
        'loket_id' => $inactiveLoket->id,
    ]);
    $historicalBap = Bap::factory()->create([
        'loket_id' => $inactiveLoket->id,
        'created_by' => $petugasLoket->id,
        'service_date' => now()->subDay()->toDateString(),
        'numerator_start' => 3_000_001,
        'numerator_end' => 3_000_010,
        'total_usage' => 10,
    ]);

    $this->actingAs($petugasLoket)
        ->get(route('baps.show', $historicalBap))
        ->assertOk();

    $this->actingAs($petugasLoket)
        ->post(route('baps.store'), operationalContextBapPayload(3_000_011, 3_000_020))
        ->assertNotFound();
});

test('selected Superadmin Loket still enforces allocation ownership and one BAP per Loket per day', function () {
    $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
    $loket = Loket::factory()->create();
    operationalContextAllocation($superadmin, $loket, 4_000_001, 4_000_020);

    $this->actingAs($superadmin)
        ->from(route('baps.create', ['loket' => $loket->id]))
        ->post(route('baps.store'), [
            ...operationalContextBapPayload(4_000_001, 4_000_021),
            'loket_id' => $loket->id,
        ])
        ->assertRedirect(route('baps.create', ['loket' => $loket->id]))
        ->assertSessionHasErrors('numerator_end');

    $this->actingAs($superadmin)
        ->post(route('baps.store'), [
            ...operationalContextBapPayload(4_000_001, 4_000_010),
            'loket_id' => $loket->id,
        ])
        ->assertRedirect();

    $this->actingAs($superadmin)
        ->from(route('baps.create', ['loket' => $loket->id]))
        ->post(route('baps.store'), [
            ...operationalContextBapPayload(4_000_011, 4_000_020),
            'loket_id' => $loket->id,
        ])
        ->assertRedirect(route('baps.create', ['loket' => $loket->id]))
        ->assertSessionHasErrors('service_date');

    $this->assertDatabaseCount('baps', 1);
});

test('Superadmin cannot change a BAP Loket or mutate submitted and completed BAPs', function () {
    $superadmin = User::factory()->create(['role' => UserRole::Superadmin]);
    $originalLoket = Loket::factory()->create();
    $otherLoket = Loket::factory()->create();
    $draftBap = Bap::factory()->create([
        'loket_id' => $originalLoket->id,
        'created_by' => $superadmin->id,
        'service_date' => now()->subDays(3)->toDateString(),
        'numerator_start' => 5_000_001,
        'numerator_end' => 5_000_010,
        'total_usage' => 10,
    ]);

    $this->actingAs($superadmin)
        ->from(route('baps.edit', $draftBap))
        ->put(route('baps.update', $draftBap), [
            ...operationalContextBapPayload(5_000_001, 5_000_010),
            'loket_id' => $otherLoket->id,
        ])
        ->assertRedirect(route('baps.edit', $draftBap))
        ->assertSessionHasErrors('loket_id');

    expect($draftBap->refresh()->loket_id)->toBe($originalLoket->id);

    $submittedBap = Bap::factory()->create([
        'loket_id' => $originalLoket->id,
        'created_by' => $superadmin->id,
        'service_date' => now()->subDays(2)->toDateString(),
        'numerator_start' => 6_000_001,
        'numerator_end' => 6_000_010,
        'total_usage' => 10,
        'status' => BapStatus::Submitted,
    ]);
    $completedBap = Bap::factory()->create([
        'loket_id' => $originalLoket->id,
        'created_by' => $superadmin->id,
        'service_date' => now()->subDay()->toDateString(),
        'numerator_start' => 7_000_001,
        'numerator_end' => 7_000_010,
        'total_usage' => 10,
        'status' => BapStatus::Completed,
    ]);

    $this->actingAs($superadmin)
        ->from(route('baps.show', $submittedBap))
        ->put(route('baps.update', $submittedBap), operationalContextBapPayload(6_000_001, 6_000_010))
        ->assertRedirect(route('baps.show', $submittedBap))
        ->assertSessionHasErrors('status');

    $this->actingAs($superadmin)
        ->from(route('baps.show', $completedBap))
        ->put(route('baps.update', $completedBap), operationalContextBapPayload(7_000_001, 7_000_010))
        ->assertRedirect(route('baps.show', $completedBap))
        ->assertSessionHasErrors('status');

    expect($submittedBap->refresh()->status)->toBe(BapStatus::Submitted)
        ->and($completedBap->refresh()->status)->toBe(BapStatus::Completed);
});
