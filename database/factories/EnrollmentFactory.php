<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Championship;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state: a submitted Enrollment of a verified Licence.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'licence_id' => Licence::factory()->verified(),
            'user_id' => User::factory(),
            'status' => EnrollmentStatus::Submitted,
        ];
    }

    /**
     * An Enrollment waiting for the given Licence Request.
     */
    public function waitingFor(LicenceRequest $licenceRequest): static
    {
        return $this->state([
            'licence_id' => null,
            'licence_request_id' => $licenceRequest->getKey(),
            'user_id' => $licenceRequest->user_id,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state([
            'status' => EnrollmentStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmed_by' => User::factory()->organizer(),
        ]);
    }
}
