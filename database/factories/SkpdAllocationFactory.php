<?php

namespace Database\Factories;

use App\Models\Loket;
use App\Models\SkpdAllocation;
use App\Models\SkpdBox;
use App\Models\User;
use App\SkpdAllocationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkpdAllocation>
 */
class SkpdAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $numeratorStart = fake()->numberBetween(1_000_000, 9_997_999);
        $numeratorEnd = $numeratorStart + 2_000 - 1;

        return [
            'skpd_box_id' => SkpdBox::factory()->state([
                'numerator_start' => $numeratorStart,
                'numerator_end' => $numeratorEnd,
                'total_sets' => 2_000,
            ]),
            'loket_id' => Loket::factory(),
            'numerator_start' => $numeratorStart,
            'numerator_end' => $numeratorEnd,
            'quantity' => 2_000,
            'status' => SkpdAllocationStatus::Pending,
            'created_by' => User::factory(),
            'accepted_by' => null,
            'accepted_at' => null,
        ];
    }
}
