<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BibReservationStatus;
use App\Models\Championship;
use App\Models\DriverLicence;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BibReservation>
 */
class BibReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'championship_id' => Championship::factory(),
            'bib' => fake()->numberBetween(1, 200),
            'driver' => fake()->name(),
            'contact_email' => fake()->email(),
        ];
    }

    public function withLicence(?string $licence = null): Factory
    {
        return $this->state(function (array $attributes) use ($licence) {

            $licenceNumber = $licence ?? fake()->numerify();

            return [
                'driver_licence_hash' => hash('sha512', $licenceNumber),
                'driver_licence' => $licenceNumber,
                'licence_type' => DriverLicence::LOCAL_NATIONAL,
            ];
        });
    }

    /**
     * A reservation picked by a driver and waiting for the organizer.
     */
    public function pending(): static
    {
        return $this->state(['status' => BibReservationStatus::Pending]);
    }

    /**
     * A reservation the organizer released.
     */
    public function released(): static
    {
        return $this->state([
            'status' => BibReservationStatus::Released,
            'released_at' => now(),
        ]);
    }

    public function expired(?CarbonInterface $expirationTime = null): Factory
    {
        return $this->state(function (array $attributes) use ($expirationTime) {
            return [
                'reservation_expires_at' => $expirationTime ?? now()->subDay(),
            ];
        });
    }
}
