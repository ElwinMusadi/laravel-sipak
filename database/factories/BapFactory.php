<?php

namespace Database\Factories;

use App\BapStatus;
use App\Models\Bap;
use App\Models\Loket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bap>
 */
class BapFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $numeratorStart = fake()->numberBetween(1_000_000, 9_999_000);
        $numeratorEnd = $numeratorStart + 9;

        return [
            'loket_id' => Loket::factory(),
            'service_date' => fake()->date(),
            'numerator_start' => $numeratorStart,
            'numerator_end' => $numeratorEnd,
            'total_usage' => 10,
            'online_usage_count' => 0,
            'status' => BapStatus::Draft,
            'created_by' => User::factory(),
            'submitted_at' => null,
        ];
    }
}
