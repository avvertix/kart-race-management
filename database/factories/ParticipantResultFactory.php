<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ResultStatus;
use App\Models\RunResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ParticipantResult>
 */
class ParticipantResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'run_result_id' => RunResult::factory(),
            'bib' => fake()->numberBetween(1, 999),
            'status' => ResultStatus::FINISHED,
            'name' => fake()->name(),
            'category' => fake()->word(),
            'position' => (string) fake()->numberBetween(1, 30),
            'position_in_category' => (string) fake()->numberBetween(1, 15),
            'gap_from_leader' => '',
            'gap_from_previous' => '',
            'best_lap_time' => fake()->numerify('##.###'),
            'best_lap_number' => (string) fake()->numberBetween(1, 10),
            'racer_hash' => fake()->sha1(),
            'is_dnf' => false,
            'is_dns' => false,
            'is_dq' => false,
            'laps' => fake()->numberBetween(5, 15),
            'total_race_time' => fake()->numerify('#:##.###'),
        ];
    }
}
