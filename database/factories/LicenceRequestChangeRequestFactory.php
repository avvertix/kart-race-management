<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenceRequestSection;
use App\Models\LicenceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenceRequestChangeRequest>
 */
class LicenceRequestChangeRequestFactory extends Factory
{
    /**
     * Define the model's default state: an open change request on the holder.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'licence_request_id' => LicenceRequest::factory(),
            'section' => LicenceRequestSection::Holder,
            'note' => 'The birth place does not match the identity card.',
            'resolved_at' => null,
        ];
    }
}
