<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Participant;
use App\Models\PaymentChannelType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * A bank transfer proof uploaded for an Enrollment or a Licence Request, waiting for the organizer.
     */
    public function proofFor(Model $payable): static
    {
        return $this->state(fn (array $attributes) => [
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'channel' => PaymentChannelType::BANK_TRANSFER,
            'status' => PaymentStatus::Submitted,
        ]);
    }

    /**
     * A payment of the given amount, in cents, the organizer confirmed for an Enrollment or a Licence Request.
     */
    public function confirmedFor(Model $payable, int $amount): static
    {
        return $this->proofFor($payable)->state(fn (array $attributes) => [
            'amount' => $amount,
            'status' => PaymentStatus::Confirmed,
            'reviewed_at' => now(),
        ]);
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
