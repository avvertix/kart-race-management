<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\DriverLicence;
use Carbon\Carbon;
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


    public function withLicence(): Factory
    {
        return $this->state(function (array $attributes) {
            
            $licenceNumber = fake()->numerify();

            return [
                'driver_licence_hash' => hash('sha512', $licenceNumber),
                'driver_licence' => $licenceNumber,
                'licence_type' => DriverLicence::LOCAL_NATIONAL,
            ];
        });
    }


    public function expired(?Carbon $expirationTime = null): Factory
    {
        return $this->state(function (array $attributes) use ($expirationTime) {
            return [
                'reservation_expires_at' => $expirationTime ?? now()->subDay(),
            ];
        });
    }
}
