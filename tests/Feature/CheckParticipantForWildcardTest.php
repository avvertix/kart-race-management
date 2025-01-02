<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ParticipantRegistered;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Models\WildcardStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckParticipantForWildcardTest extends TestCase
{
    use RefreshDatabase;

    public function test_championship_wildcard_disabled(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => false,
                    'strategy' => WildcardStrategy::BASED_ON_FIRST_RACE,
                ],
            ]);

        $race = $championship->races()->first();

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->create();

        event(new ParticipantRegistered($participant, $race));

        $updatedParticipant = $participant->fresh();

        $this->assertNull($updatedParticipant->wildcard);
    }

    public function test_wildcard_not_set_if_participant_registers_to_first_race(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_FIRST_RACE,
                ],
            ]);

        $race = $championship->races()->first();

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->create();

        event(new ParticipantRegistered($participant, $race));

        $updatedParticipant = $participant->fresh();

        $this->assertFalse($updatedParticipant->wildcard);
    }

    public function test_wildcard_if_participant_registers_for_the_first_time_after_the_first_race(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory()->count(2)->sequence(
                ['event_start_at' => now()->subDays(3), 'event_end_at' => now()->subDays(3)],
                ['event_start_at' => now()->subDay(), 'event_end_at' => now()->addDays(1)],
            ))
            ->create([
            'wildcard' => [
                'enabled' => true,
                'strategy' => WildcardStrategy::BASED_ON_FIRST_RACE,
            ],
            ]);

        $race = $championship->races[1];

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->create();

        event(new ParticipantRegistered($participant, $race));

        $updatedParticipant = $participant->fresh();

        $this->assertTrue($updatedParticipant->wildcard);
    }

    public function test_canceled_races_are_skipped(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory()->count(2)->sequence(
                ['event_start_at' => now()->subDays(3), 'event_end_at' => now()->subDays(3), 'canceled_at' => now()->subDays(2)],
                ['event_start_at' => now()->subDay(), 'event_end_at' => now()->addDays(1)],
            ))
            ->create([
            'wildcard' => [
                'enabled' => true,
                'strategy' => WildcardStrategy::BASED_ON_FIRST_RACE,
            ],
            ]);

        $race = $championship->races[1];

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->create();

        event(new ParticipantRegistered($participant, $race));

        $updatedParticipant = $participant->fresh();

        $this->assertFalse($updatedParticipant->wildcard);
    }
}
