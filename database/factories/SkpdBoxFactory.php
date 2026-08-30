<?php

namespace Database\Factories;

use App\Models\SkpdBox;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkpdBox>
 */
class SkpdBoxFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $numeratorStart = fake()->unique()->numberBetween(1_000_000, 9_997_999);
        $numeratorEnd = $numeratorStart + 2_000 - 1;

        return [
            'box_number' => 'BOX-'.fake()->unique()->bothify('####??'),
            'numerator_start' => $numeratorStart,
            'numerator_end' => $numeratorEnd,
            'total_sets' => 2_000,
            'central_storage_location' => 'Bendahara Barang',
            'created_by' => User::factory(),
            'received_at' => now(),
        ];
    }
}
