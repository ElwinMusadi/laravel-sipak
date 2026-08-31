<?php

namespace Database\Factories;

use App\BapVerificationChecklistType;
use App\Models\BapVerification;
use App\Models\BapVerificationChecklistItem;
use App\Models\BapVerificationDiscrepancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BapVerificationDiscrepancy>
 */
class BapVerificationDiscrepancyFactory extends Factory
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
            'bap_verification_checklist_item_id' => BapVerificationChecklistItem::factory(),
            'type' => BapVerificationChecklistType::UsageQuantity,
            'expected_value' => '13',
            'actual_value' => '12',
            'difference' => -1,
            'notes' => 'Satu set tindisan belum ditemukan.',
        ];
    }

    public function forChecklistItem(BapVerificationChecklistItem $item): static
    {
        return $this->state([
            'bap_verification_id' => $item->bap_verification_id,
            'bap_verification_checklist_item_id' => $item->id,
            'type' => $item->type,
        ]);
    }
}
