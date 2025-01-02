<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommunicationMessage>
 */
class CommunicationMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'message' => fake()->sentence(),
            'theme' => 'info',
            'target_path' => null,
            'target_user_role' => null,
            'starts_at' => null,
            'ends_at' => null,
            'dismissable' => false,
        ];
    }
}
