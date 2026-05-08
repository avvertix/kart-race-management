<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommunicationType;
use App\Models\Race;
use App\Models\RaceCommunication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RaceCommunication>
 */
class RaceCommunicationFactory extends Factory
{
    public function definition(): array
    {
        $race = Race::factory()->create();

        return [
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'user_id' => User::factory(),
            'type' => CommunicationType::Communication->value,
            'run_type' => null,
            'message' => fake()->sentence(),
            'read_at' => null,
        ];
    }
}
