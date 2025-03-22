<?php

declare(strict_types=1);

namespace Tests\Feature\Wildcards;

use App\Actions\Wildcard\AttributeWildcardBasedOnBonus;
use App\Models\Bonus;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Models\WildcardStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeWildcardBasedOnBonusTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_identified_as_wild_card(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BONUS,
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

        $isWildcard = (new AttributeWildcardBasedOnBonus)($participant, $race);

        $this->assertTrue($isWildcard);
    }

    public function test_participant_identified_as_wild_card_when_bonus_amount_less_than_required(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BONUS,
                    'requiredBonusAmount' => 2,
                ],
            ]);

        $bonus = Bonus::factory()
            ->recycle($championship)
            ->create([
                'driver_licence' => 'LN1',
                'driver_licence_hash' => hash('sha512', 'LN1'),
                'amount' => 1,
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

        $isWildcard = (new AttributeWildcardBasedOnBonus)($participant, $race);

        $this->assertTrue($isWildcard);
    }

    public function test_participant_not_a_wild_card(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BONUS,
                    'requiredBonusAmount' => 2,
                ],
            ]);

        $bonus = Bonus::factory()
            ->recycle($championship)
            ->create([
                'driver_licence' => 'LN1',
                'driver_licence_hash' => hash('sha512', 'LN1'),
                'amount' => 2,
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

        $isWildcard = (new AttributeWildcardBasedOnBonus)($participant, $race);

        $this->assertFalse($isWildcard);
    }

    public function test_participant_not_a_wild_card_when_wildcard_disabled(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => false,
                    'strategy' => WildcardStrategy::BASED_ON_BONUS,
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

        $isWildcard = (new AttributeWildcardBasedOnBonus)($participant, $race);

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

        $isWildcard = (new AttributeWildcardBasedOnBonus)($participant, $race);

        $this->assertFalse($isWildcard);
    }

    public function test_participant_not_a_wild_card_when_wildcard_strategy_is_different(): void
    {
        $championship = Championship::factory()
            ->has(Race::factory())
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION,
                ],
            ]);

        $bonus = Bonus::factory()
            ->recycle($championship)
            ->create([
                'driver_licence' => 'LN1',
                'driver_licence_hash' => hash('sha512', 'LN1'),
                'amount' => 2,
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

        $isWildcard = (new AttributeWildcardBasedOnBonus)($participant, $race);

        $this->assertFalse($isWildcard);
    }
}
