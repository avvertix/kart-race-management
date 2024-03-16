<?php

namespace Database\Factories;

use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'hash' => fake()->md5(),
            'path' => 'proof.jpg',
        ];
    }


    public function forParticipant(?Participant $participant = null)
    {
        return $this->state(function (array $attributes) use ($participant) {
            return [
                'participant_id' => $participant?->getKey() ?? Participant::factory(),
            ];
        });
    }
}
