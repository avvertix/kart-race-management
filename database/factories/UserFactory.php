<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Indicate that the user's role is admin.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function admin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'admin',
            ];
        });
    }

    /**
     * Indicate that the user's role is organizer.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function organizer(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'organizer',
            ];
        });
    }
    /**
     * Indicate that the user's role is racemanager.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function racemanager(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'racemanager',
            ];
        });
    }
    /**
     * Indicate that the user's role is tireagent.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function tireagent(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'tireagent',
            ];
        });
    }
    /**
     * Indicate that the user's role is timekeeper.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function timekeeper(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'role' => 'timekeeper',
            ];
        });
    }
}
