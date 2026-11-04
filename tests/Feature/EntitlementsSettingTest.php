<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\LicencesAndProducts;
use App\Models\BonusMode;
use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class EntitlementsSettingTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_existing_championships_and_races_have_entitlements_off(): void
    {
        $race = Race::factory()->create();

        $this->assertFalse($race->championship->entitlements_enabled);
        $this->assertNull($race->entitlements_enabled);
        $this->assertFalse($race->isEntitlementEnabled());
        $this->assertTrue($race->isBonusEnabled());
    }

    public function test_enabling_entitlements_needs_bonuses_off(): void
    {
        $organizer = User::factory()->organizer()->create();

        foreach ([null, true] as $bonusEnabled) {
            $championship = Championship::factory()->create(['bonus_enabled' => $bonusEnabled]);

            $this->actingAs($organizer)
                ->put(route('championships.entitlement-settings.update', $championship), ['entitlements_enabled' => '1'])
                ->assertSessionHasErrors('entitlements_enabled');

            $this->assertFalse($championship->fresh()->entitlements_enabled);
        }

        $championship = Championship::factory()->create(['bonus_enabled' => false]);

        $this->actingAs($organizer)
            ->put(route('championships.entitlement-settings.update', $championship), ['entitlements_enabled' => '1'])
            ->assertRedirect(route('championships.edit', $championship));

        $this->assertTrue($championship->fresh()->entitlements_enabled);

        $this->actingAs($organizer)
            ->put(route('championships.bonus-settings.update', $championship), [
                'bonus_mode' => BonusMode::CREDIT->value,
                'bonus_enabled' => 'true',
            ])
            ->assertSessionHasErrors('bonus_enabled');

        $this->assertFalse($championship->fresh()->bonus_enabled);
    }

    public function test_a_race_follows_the_championship_or_opts_out(): void
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);
        $race = Race::factory()->recycle($championship)->create();
        $optedOut = Race::factory()->recycle($championship)->create(['entitlements_enabled' => false]);

        $this->assertTrue($race->isEntitlementEnabled());
        $this->assertFalse($optedOut->isEntitlementEnabled());
        $this->assertFalse(Race::factory()->create(['entitlements_enabled' => true])->isEntitlementEnabled());
    }

    public function test_the_organizer_opts_a_race_out(): void
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);
        $race = Race::factory()->recycle($championship)->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('races.edit', $race))
            ->assertSee(__('Promo codes and packages'));

        $this->actingAs($organizer)
            ->put(route('races.update', $race), [
                'start' => $race->event_start_at->toDateString(),
                'end' => null,
                'title' => $race->title,
                'description' => null,
                'track' => $race->track,
                'participants_total_limit' => null,
                'entitlements_enabled' => 'false',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($race->fresh()->isEntitlementEnabled());

        $this->travelTo($race->registration_closes_at->subHour());

        $this->get(route('races.registration.create', $race))
            ->assertSee(__('Promo codes don\'t apply to this race.'));
    }

    public function test_the_setting_needs_early_access(): void
    {
        Feature::define(LicencesAndProducts::class, fn () => false);

        $championship = Championship::factory()->create(['bonus_enabled' => false]);

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('championships.entitlement-settings.update', $championship), ['entitlements_enabled' => '1'])
            ->assertNotFound();
    }
}
