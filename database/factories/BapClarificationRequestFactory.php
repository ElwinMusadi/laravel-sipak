<?php

namespace Database\Factories;

use App\BapClarificationStatus;
use App\Models\Bap;
use App\Models\BapClarificationRequest;
use App\Models\BapVerification;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BapClarificationRequest>
 */
class BapClarificationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bap_id' => Bap::factory(),
            'bap_verification_id' => BapVerification::factory(),
            'requested_by' => User::factory()->state([
                'role' => UserRole::PetugasPenetapan,
            ]),
            'status' => BapClarificationStatus::Open,
            'notes' => 'Perlu konfirmasi atas selisih pemeriksaan fisik.',
        ];
    }

    public function forVerification(BapVerification $verification): static
    {
        return $this->state([
            'bap_id' => $verification->bap_id,
            'bap_verification_id' => $verification->id,
            'requested_by' => $verification->verifier_id,
        ]);
    }
}
