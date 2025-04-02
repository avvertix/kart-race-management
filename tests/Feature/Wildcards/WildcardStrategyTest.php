<?php

declare(strict_types=1);

namespace Tests\Feature\Wildcards;

use App\Actions\Wildcard\AttributeWildcardBasedOnBibReservation;
use App\Actions\Wildcard\AttributeWildcardBasedOnBonus;
use App\Models\WildcardStrategy;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class WildcardStrategyTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_bonus_strategy_implementation_returned(): void
    {
        $strategyClass = WildcardStrategy::BASED_ON_BONUS->resolve();

        $this->assertInstanceOf(AttributeWildcardBasedOnBonus::class, $strategyClass);
    }

    public function test_reservation_strategy_implementation_returned(): void
    {
        $strategyClass = WildcardStrategy::BASED_ON_BIB_RESERVATION->resolve();

        $this->assertInstanceOf(AttributeWildcardBasedOnBibReservation::class, $strategyClass);
    }
}
