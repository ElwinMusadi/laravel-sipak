<?php

namespace Database\Seeders;

use App\Actions\SkpdInventory\AcceptSkpdAllocation;
use App\Actions\SkpdInventory\CreateSkpdAllocation;
use App\Actions\SkpdInventory\RegisterSkpdBox;
use App\Models\Loket;
use App\Models\SkpdAllocation;
use App\Models\SkpdBox;
use App\Models\User;
use App\SkpdAllocationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use LogicException;

class DevelopmentWorkflowSeeder extends Seeder
{
    /**
     * Seed reproducible inventory prerequisites for browser-based development workflows.
     */
    public function run(
        RegisterSkpdBox $registerSkpdBox,
        CreateSkpdAllocation $createSkpdAllocation,
        AcceptSkpdAllocation $acceptSkpdAllocation,
    ): void {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach ($this->fixtureDefinitions() as $definition) {
            $box = $this->findOrRegisterBox($definition, $registerSkpdBox);
            $allocation = $this->findOrCreateAllocation($definition, $box, $createSkpdAllocation);

            $this->ensureAccepted($definition, $allocation, $acceptSkpdAllocation);
        }
    }

    /**
     * @return list<array{box_number: string, loket_code: string, numerator_start: int, numerator_end: int, receiver_username: string}>
     */
    private function fixtureDefinitions(): array
    {
        return [
            [
                'box_number' => 'DEV-MPP-001',
                'loket_code' => 'MPP',
                'numerator_start' => 582_001,
                'numerator_end' => 584_000,
                'receiver_username' => 'elwinmusadi16',
            ],
            [
                'box_number' => 'DEV-SAMSAT-KANTOR-001',
                'loket_code' => 'SAMSAT-KANTOR',
                'numerator_start' => 584_001,
                'numerator_end' => 586_000,
                'receiver_username' => 'simson.sae',
            ],
        ];
    }

    /**
     * @param  array{box_number: string, loket_code: string, numerator_start: int, numerator_end: int, receiver_username: string}  $definition
     */
    private function findOrRegisterBox(array $definition, RegisterSkpdBox $registerSkpdBox): SkpdBox
    {
        $box = SkpdBox::query()->where('box_number', $definition['box_number'])->first();

        if ($box === null) {
            return $registerSkpdBox->handle(
                $this->requiredUser('yunus.asamani'),
                $definition['box_number'],
                $definition['numerator_start'],
                $definition['numerator_end'],
                CarbonImmutable::parse('2026-09-01 08:00:00'),
            );
        }

        if (
            $box->numerator_start !== $definition['numerator_start']
            || $box->numerator_end !== $definition['numerator_end']
            || $box->total_sets !== $definition['numerator_end'] - $definition['numerator_start'] + 1
        ) {
            throw new LogicException("Box fixture {$definition['box_number']} memiliki range yang tidak sesuai.");
        }

        return $box;
    }

    /**
     * @param  array{box_number: string, loket_code: string, numerator_start: int, numerator_end: int, receiver_username: string}  $definition
     */
    private function findOrCreateAllocation(
        array $definition,
        SkpdBox $box,
        CreateSkpdAllocation $createSkpdAllocation,
    ): SkpdAllocation {
        $loket = $this->requiredLoket($definition['loket_code']);
        $allocations = SkpdAllocation::query()
            ->whereBelongsTo($box, 'skpdBox')
            ->whereBelongsTo($loket)
            ->where('numerator_start', $definition['numerator_start'])
            ->where('numerator_end', $definition['numerator_end'])
            ->get();

        if ($allocations->count() > 1) {
            throw new LogicException("Alokasi fixture {$definition['box_number']} terduplikasi.");
        }

        $allocation = $allocations->first();

        if ($allocation === null) {
            return $createSkpdAllocation->handle(
                $this->requiredUser('yunus.asamani'),
                $box,
                $loket,
                CarbonImmutable::parse('2026-09-01'),
                $definition['numerator_start'],
                $definition['numerator_end'],
            );
        }

        if ($allocation->quantity !== $definition['numerator_end'] - $definition['numerator_start'] + 1) {
            throw new LogicException("Alokasi fixture {$definition['box_number']} memiliki kuantitas yang tidak sesuai.");
        }

        return $allocation;
    }

    /**
     * @param  array{box_number: string, loket_code: string, numerator_start: int, numerator_end: int, receiver_username: string}  $definition
     */
    private function ensureAccepted(
        array $definition,
        SkpdAllocation $allocation,
        AcceptSkpdAllocation $acceptSkpdAllocation,
    ): void {
        if ($allocation->status === SkpdAllocationStatus::Accepted) {
            return;
        }

        if ($allocation->status !== SkpdAllocationStatus::Pending) {
            throw new LogicException("Alokasi fixture {$definition['box_number']} tidak lagi siap untuk pengujian manual.");
        }

        $acceptSkpdAllocation->handle(
            $this->requiredUser($definition['receiver_username']),
            $allocation,
        );
    }

    private function requiredLoket(string $code): Loket
    {
        $loket = Loket::query()->where('code', $code)->first();

        if ($loket === null) {
            throw new LogicException("Loket development {$code} tidak tersedia.");
        }

        return $loket;
    }

    private function requiredUser(string $username): User
    {
        $user = User::query()->where('username', $username)->first();

        if ($user === null) {
            throw new LogicException("Akun development {$username} tidak tersedia.");
        }

        return $user;
    }
}
