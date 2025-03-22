<?php

declare(strict_types=1);

namespace Tests\Feature\Wildcards;

use App\Actions\Wildcard\AttributeWildcardBasedOnBibReservation;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Models\WildcardStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeWildcardBasedOnBibReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_identified_as_wild_card(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION,
                ],
            ]);

        $race = $championship->races->first();

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->driver([
                'bib' => 100,
                'licence_number' => 'LN1',
            ])
            ->create();

        $isWildcard = (new AttributeWildcardBasedOnBibReservation)($participant, $race);

        $this->assertTrue($isWildcard);
    }

    public function test_participant_not_a_wild_card(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION,
                ],
            ]);

        $reservation = BibReservation::factory()
            ->recycle($championship)
            ->withLicence('LN1')
            ->create([
                'bib' => '100',
                'driver' => 'Driver name',
            ]);

        $race = $championship->races->first();

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->driver([
                'bib' => 100,
                'licence_number' => 'LN1',
            ])
            ->create();

        $isWildcard = (new AttributeWildcardBasedOnBibReservation)($participant, $race);

        $this->assertFalse($isWildcard);
    }

    public function test_participant_not_a_wild_card_when_wildcard_disabled(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => false,
                    'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION,
                ],
            ]);

        $race = $championship->races->first();

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->driver([
                'bib' => 100,
                'licence_number' => 'LN1',
            ])
            ->create();

        $isWildcard = (new AttributeWildcardBasedOnBibReservation)($participant, $race);

        $this->assertFalse($isWildcard);
    }

    public function test_participant_not_a_wild_card_when_wildcard_settings_omitted(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create();

        $race = $championship->races->first();

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->driver([
                'bib' => 100,
                'licence_number' => 'LN1',
            ])
            ->create();

        $isWildcard = (new AttributeWildcardBasedOnBibReservation)($participant, $race);

        $this->assertFalse($isWildcard);
    }

    public function test_participant_not_a_wild_card_when_wildcard_strategy_is_different(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BONUS,
                ],
            ]);

        $reservation = BibReservation::factory()
            ->recycle($championship)
            ->withLicence('LN1')
            ->create([
                'bib' => '100',
                'driver' => 'Driver name',
            ]);

        $race = $championship->races->first();

        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->driver([
                'bib' => 100,
                'licence_number' => 'LN1',
            ])
            ->create();

        $isWildcard = (new AttributeWildcardBasedOnBibReservation)($participant, $race);

        $this->assertFalse($isWildcard);
    }
}
