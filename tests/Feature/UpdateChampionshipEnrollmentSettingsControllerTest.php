<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\LicencesAndProducts;
use App\Models\Championship;
use App\Models\EnrollmentOption;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class UpdateChampionshipEnrollmentSettingsControllerTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_an_existing_championship_has_enrollment_off(): void
    {
        $settings = Championship::factory()->create()->enrollment;

        $this->assertFalse($settings->enabled);
        $this->assertTrue($settings->requires_competitor_when_underage);
        $this->assertFalse($settings->isOpen());
    }

    public function test_the_settings_need_an_organizer(): void
    {
        $championship = Championship::factory()->create();

        $this->actingAs(User::factory()->racemanager()->create())
            ->get(route('championships.enrollment-settings.edit', $championship))
            ->assertForbidden();
    }

    public function test_the_settings_need_early_access(): void
    {
        Feature::define(LicencesAndProducts::class, fn () => false);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.enrollment-settings.edit', Championship::factory()->create()))
            ->assertNotFound();
    }

    public function test_the_organizer_enables_enrollment_with_an_option(): void
    {
        config(['races.timezone' => 'Europe/Rome']);

        $championship = Championship::factory()->create();
        $option = EnrollmentOption::factory()->for($championship)->create(['title' => 'BKQ enrollment']);
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('championships.enrollment-settings.edit', $championship))
            ->assertSuccessful()
            ->assertSee($option->title);

        $this->actingAs($organizer)
            ->put(route('championships.enrollment-settings.update', $championship), [
                'enabled' => '1',
                'opens_at' => '2027-01-10T09:00',
                'closes_at' => '2027-02-28T23:59',
            ])
            ->assertRedirect(route('championships.show', $championship));

        $settings = $championship->fresh()->enrollment;

        $this->assertTrue($settings->enabled);
        $this->assertTrue($settings->opens_at->equalTo(CarbonImmutable::parse('2027-01-10 09:00', 'Europe/Rome')));
        $this->assertTrue($settings->closes_at->equalTo(CarbonImmutable::parse('2027-02-28 23:59', 'Europe/Rome')));
        $this->assertFalse($settings->requires_competitor_when_underage);

        $this->assertFalse($settings->isOpen(CarbonImmutable::parse('2027-01-10 08:59', 'Europe/Rome')));
        $this->assertTrue($settings->isOpen(CarbonImmutable::parse('2027-01-10 09:00', 'Europe/Rome')));
        $this->assertFalse($settings->isOpen(CarbonImmutable::parse('2027-03-01 00:00', 'Europe/Rome')));
    }

    public function test_enrollment_needs_the_period(): void
    {
        $championship = Championship::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->put(route('championships.enrollment-settings.update', $championship), [
                'enabled' => '1',
            ])
            ->assertSessionHasErrors(['opens_at', 'closes_at']);

        $this->actingAs($organizer)
            ->put(route('championships.enrollment-settings.update', $championship), [
                'enabled' => '1',
                'opens_at' => '2027-02-10T09:00',
                'closes_at' => '2027-02-01T09:00',
            ])
            ->assertSessionHasErrors(['closes_at']);

        $this->assertFalse($championship->fresh()->enrollment->enabled);
    }

    public function test_enrollment_needs_an_option_offered_to_drivers(): void
    {
        $championship = Championship::factory()->create();
        EnrollmentOption::factory()->for($championship)->create(['active' => false]);

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('championships.enrollment-settings.update', $championship), [
                'enabled' => '1',
                'opens_at' => '2027-01-10T09:00',
                'closes_at' => '2027-02-28T23:59',
            ])
            ->assertSessionHasErrors(['enabled']);

        $this->assertFalse($championship->fresh()->enrollment->enabled);
    }

    public function test_disabling_keeps_the_rest_of_the_settings(): void
    {
        $championship = Championship::factory()->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('championships.enrollment-settings.update', $championship), [
                'opens_at' => '2027-01-10T09:00',
                'closes_at' => '',
                'requires_competitor_when_underage' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('championships.enrollment-settings.update', $championship), [
                'opens_at' => '',
                'closes_at' => '2027-02-28T23:59',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('championships.enrollment-settings.update', $championship), [
                'opens_at' => '2027-01-10T09:00',
                'closes_at' => '',
                'requires_competitor_when_underage' => '1',
            ])
            ->assertSessionHasNoErrors();

        $settings = $championship->fresh()->enrollment;

        $this->assertFalse($settings->enabled);
        $this->assertNotNull($settings->opens_at);
        $this->assertNull($settings->closes_at);
        $this->assertTrue($settings->requires_competitor_when_underage);
    }
}
