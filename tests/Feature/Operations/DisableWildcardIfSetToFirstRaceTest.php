<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Data\WildcardSettingsData;
use App\Models\Championship;
use App\Models\WildcardStrategy;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class DisableWildcardIfSetToFirstRaceTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_wildcard_disabled_when_using_first_race_strategy()
    {
        $championship = Championship::factory()
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_FIRST_RACE,
                ],
            ]);

        $this->artisan('operations:process 2025_03_22_105802_disable_wildcard_if_set_to_first_race')
            ->assertSuccessful();

        /**
         * @var WildcardSettingsData $wildcard
         */
        $wildcard = $championship->fresh()->wildcard;

        $this->assertFalse($wildcard->enabled);
        $this->assertNull($wildcard->strategy);
    }

    public function test_wildcard_settings_not_modified()
    {
        $championship = Championship::factory()
            ->create([
                'wildcard' => [
                    'enabled' => true,
                    'strategy' => WildcardStrategy::BASED_ON_BONUS,
                ],
            ]);

        $this->artisan('operations:process 2025_03_22_105802_disable_wildcard_if_set_to_first_race')
            ->assertSuccessful();

        /**
         * @var WildcardSettingsData $wildcard
         */
        $wildcard = $championship->fresh()->wildcard;

        $this->assertTrue($wildcard->enabled);
        $this->assertEquals(WildcardStrategy::BASED_ON_BONUS, $wildcard->strategy);
    }

    public function test_strategy_deselected()
    {
        $championship = Championship::factory()
            ->create([
                'wildcard' => [
                    'enabled' => false,
                    'strategy' => WildcardStrategy::BASED_ON_FIRST_RACE,
                ],
            ]);

        $this->artisan('operations:process 2025_03_22_105802_disable_wildcard_if_set_to_first_race')
            ->assertSuccessful();

        /**
         * @var WildcardSettingsData $wildcard
         */
        $wildcard = $championship->fresh()->wildcard;

        $this->assertFalse($wildcard->enabled);
        $this->assertNull($wildcard->strategy);
    }
}
