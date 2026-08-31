<?php

namespace Database\Factories;

use App\BapClarificationResolutionOutcome;
use App\Models\BapClarificationRequest;
use App\Models\BapClarificationResolution;
use App\Models\BapClarificationResponse;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BapClarificationResolution>
 */
class BapClarificationResolutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bap_clarification_request_id' => BapClarificationRequest::factory(),
            'bap_clarification_response_id' => BapClarificationResponse::factory(),
            'resolved_by' => User::factory()->state(['role' => UserRole::PetugasPenetapan]),
            'outcome' => BapClarificationResolutionOutcome::Resolved,
            'notes' => 'Penyelesaian diterima setelah pemeriksaan ulang.',
            'resolved_at' => now(),
        ];
    }
}
