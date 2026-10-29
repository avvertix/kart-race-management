<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\AddressData;
use App\Data\HolderData;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Enums\LicenceSource;
use App\Models\DriverLicence;
use App\Models\Sex;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Licence>
 */
class LicenceFactory extends Factory
{
    /**
     * A holder with plausible details.
     */
    public static function holder(string $firstName = 'Mario', string $lastName = 'Rossi'): HolderData
    {
        return new HolderData(
            first_name: $firstName,
            last_name: $lastName,
            sex: Sex::MALE,
            birth_date: '2008-05-14',
            birth_place: 'Bergamo',
            fiscal_code: null,
            nationality: 'Italian',
            email: 'holder@example.com',
            phone: '+39 333 1234567',
            residence_address: new AddressData(
                address: 'Via Roma 1',
                city: 'Bergamo',
                province: 'BG',
                postal_code: '24121',
            ),
        );
    }

    /**
     * Define the model's default state: an ACI NAZ E driver licence declared by its manager, not yet verified.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => LicenceProvider::Aci,
            'licence_type' => 'naz-e',
            'includes_competitor' => false,
            'role' => LicenceRole::Driver,
            'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
            'number' => (string) fake()->unique()->numberBetween(100000, 999999),
            'validity_year' => now()->year,
            'holder' => self::holder(),
            'source' => LicenceSource::Declared,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_at' => now(),
            'verified_by' => User::factory()->organizer(),
        ]);
    }

    public function emitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => LicenceSource::Emitted,
            'temporary_licence_path' => 'temporary-licences/licence.pdf',
        ]);
    }
}
