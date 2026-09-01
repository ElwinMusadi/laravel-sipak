<?php

use App\Models\Loket;
use App\Models\SkpdAllocation;
use App\Models\SkpdBox;
use App\Models\User;
use App\SkpdAllocationStatus;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentUserSeeder;
use Illuminate\Support\Facades\Hash;

test('seeds idempotent accepted workflow fixtures without changing development accounts', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $superadmin = User::query()->where('username', 'elwinmusadi16')->firstOrFail();
    $petugasLoket = User::query()->where('username', 'simson.sae')->firstOrFail();
    $mppBox = SkpdBox::query()->where('box_number', 'DEV-MPP-001')->firstOrFail();
    $kantorBox = SkpdBox::query()->where('box_number', 'DEV-SAMSAT-KANTOR-001')->firstOrFail();

    $this->assertDatabaseCount('users', 7);
    $this->assertDatabaseCount('lokets', 4);
    $this->assertDatabaseCount('skpd_boxes', 2);
    $this->assertDatabaseCount('skpd_allocations', 2);
    $this->assertDatabaseCount('audit_logs', 6);
    $this->assertDatabaseCount('baps', 0);
    $this->assertDatabaseHas('users', [
        'id' => $superadmin->id,
        'role' => 'superadmin',
        'loket_id' => null,
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $petugasLoket->id,
        'role' => 'petugas_loket',
        'loket_id' => $petugasLoket->loket_id,
    ]);
    $this->assertDatabaseHas('skpd_boxes', [
        'id' => $mppBox->id,
        'numerator_start' => 582_001,
        'numerator_end' => 584_000,
        'total_sets' => 2_000,
    ]);
    $this->assertDatabaseHas('skpd_boxes', [
        'id' => $kantorBox->id,
        'numerator_start' => 584_001,
        'numerator_end' => 586_000,
        'total_sets' => 2_000,
    ]);

    $mppAllocation = SkpdAllocation::query()->whereBelongsTo($mppBox, 'skpdBox')->firstOrFail();
    $kantorAllocation = SkpdAllocation::query()->whereBelongsTo($kantorBox, 'skpdBox')->firstOrFail();

    expect($mppAllocation->status)->toBe(SkpdAllocationStatus::Accepted)
        ->and($mppAllocation->accepted_by)->toBe($superadmin->id)
        ->and($mppAllocation->accepted_at)->not->toBeNull()
        ->and($kantorAllocation->status)->toBe(SkpdAllocationStatus::Accepted)
        ->and($kantorAllocation->accepted_by)->toBe($petugasLoket->id)
        ->and($kantorAllocation->accepted_at)->not->toBeNull();
});

test('preserves existing development account credentials and Loket master data', function () {
    $mpp = Loket::factory()->create([
        'code' => 'MPP',
        'name' => 'Mall Pelayanan Publik Saat Ini',
        'is_active' => false,
    ]);
    $superadmin = User::factory()->create([
        'name' => 'Elwin Existing',
        'username' => 'elwinmusadi16',
        'password' => Hash::make('credential-existing'),
        'role' => 'superadmin',
        'loket_id' => null,
    ]);

    app(DevelopmentUserSeeder::class)->run();

    $this->assertDatabaseCount('users', 7);
    $this->assertDatabaseCount('lokets', 4);
    $this->assertDatabaseHas('users', [
        'id' => $superadmin->id,
        'name' => 'Elwin Existing',
        'role' => 'superadmin',
        'loket_id' => null,
    ]);
    $this->assertDatabaseHas('lokets', [
        'id' => $mpp->id,
        'code' => 'MPP',
        'name' => 'Mall Pelayanan Publik Saat Ini',
        'is_active' => false,
    ]);

    expect(Hash::check('credential-existing', $superadmin->fresh()->password))->toBeTrue();
});
