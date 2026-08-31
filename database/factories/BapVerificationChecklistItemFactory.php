<?php

namespace Database\Factories;

use App\BapVerificationChecklistType;
use App\Models\BapVerification;
use App\Models\BapVerificationChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BapVerificationChecklistItem>
 */
class BapVerificationChecklistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bap_verification_id' => BapVerification::factory(),
            'type' => BapVerificationChecklistType::UsageQuantity,
            'is_attested' => true,
            'expected_quantity' => 13,
            'actual_quantity' => 13,
            'quantity_difference' => 0,
            'expected_numerator_start' => null,
            'expected_numerator_end' => null,
            'actual_numerator_start' => null,
            'actual_numerator_end' => null,
        ];
    }

    public function numeratorRange(int $start = 582_608, int $end = 582_620): static
    {
        return $this->state([
            'type' => BapVerificationChecklistType::Numerator,
            'expected_quantity' => $end - $start + 1,
            'actual_quantity' => $end - $start + 1,
            'quantity_difference' => 0,
            'expected_numerator_start' => $start,
            'expected_numerator_end' => $end,
            'actual_numerator_start' => $start,
            'actual_numerator_end' => $end,
        ]);
    }
}
