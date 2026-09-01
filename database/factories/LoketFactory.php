<?php

namespace Database\Factories;

use App\Models\Loket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loket>
 */
class LoketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'LOKET-'.fake()->unique()->numerify('###'),
            'name' => 'Loket '.fake()->unique()->numberBetween(1, 999),
            'description' => null,
            'is_active' => true,
        ];
    }
}
