<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\EntitlementMode;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\EnrollmentOption;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class EnrollmentOptionTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_organizer_configures_the_kartsport_options(): void
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('championships.enrollment-settings.edit', $championship))
            ->assertOk()
            ->assertSee(route('championships.enrollment-options.create', $championship));

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $championship), ['title' => 'Free', 'amount' => '0'])
            ->assertRedirect(route('championships.enrollment-settings.edit', $championship));

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $championship), [
                'title' => 'Promo Easyrace',
                'amount' => '150.50',
                'covers_licence_renewal' => '1',
                'reserves_race_number' => '1',
                'entitlement_mode' => EntitlementMode::Credit->value,
                'entitlement_amount' => '1',
                'licence_categories' => [(string) DriverLicence::EASYRACE->value],
            ])
            ->assertSessionHasNoErrors();

        $promo = EnrollmentOption::query()->where('title', 'Promo Easyrace')->sole();

        $this->assertSame(15050, $promo->amount);
        $this->assertTrue($promo->covers_licence_renewal);
        $this->assertTrue($promo->reserves_race_number);
        $this->assertSame(EntitlementMode::Credit, $promo->entitlement_mode);
        $this->assertSame(1, $promo->entitlement_amount);
        $this->assertTrue($promo->isFor(DriverLicence::EASYRACE));
        $this->assertFalse($promo->isFor(DriverLicence::LOCAL_NATIONAL));
        $this->assertTrue($championship->enrollmentOptions()->where('title', 'Free')->sole()->isFor(DriverLicence::LOCAL_NATIONAL));
        $this->assertSame(['Free', 'Promo Easyrace'], $championship->enrollmentOptions()->pluck('title')->all());

        $this->actingAs($organizer)
            ->get(route('championships.enrollment-settings.edit', $championship))
            ->assertSee('Promo Easyrace')
            ->assertSee('€150.50');
    }

    public function test_the_organizer_changes_and_deactivates_an_option(): void
    {
        $option = EnrollmentOption::factory()->create(['title' => 'Enrollment', 'amount' => 18000]);
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('enrollment-options.edit', $option))
            ->assertOk()
            ->assertSee('180.00');

        $this->actingAs($organizer)
            ->put(route('enrollment-options.update', $option), ['title' => 'BKQ enrollment', 'amount' => '200', 'covers_licence_renewal' => '1'])
            ->assertRedirect(route('championships.enrollment-settings.edit', $option->championship));

        $option = $option->fresh();

        $this->assertSame('BKQ enrollment', $option->title);
        $this->assertSame(20000, $option->amount);
        $this->assertTrue($option->covers_licence_renewal);
        $this->assertFalse($option->active);
    }

    public function test_an_option_needs_a_valid_amount_and_race_credits(): void
    {
        $withEntitlements = Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);
        $withoutEntitlements = Championship::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $withEntitlements), ['title' => 'Bad', 'amount' => '-1'])
            ->assertSessionHasErrors('amount');

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $withEntitlements), ['title' => 'Bad', 'amount' => '10', 'entitlement_mode' => EntitlementMode::Credit->value])
            ->assertSessionHasErrors('entitlement_amount');

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $withoutEntitlements), ['title' => 'Bad', 'amount' => '10', 'entitlement_mode' => EntitlementMode::Credit->value, 'entitlement_amount' => '1'])
            ->assertSessionHasErrors('entitlement_mode');

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $withEntitlements), ['title' => 'Bad', 'amount' => '10', 'entitlement_mode' => EntitlementMode::Credit->value, 'entitlement_amount' => '1.5'])
            ->assertSessionHasErrors('entitlement_amount');

        $this->assertSame(0, EnrollmentOption::query()->count());

        $this->actingAs($organizer)
            ->post(route('championships.enrollment-options.store', $withEntitlements), ['title' => 'Balance', 'amount' => '10', 'entitlement_mode' => EntitlementMode::Balance->value, 'entitlement_amount' => '25.50'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2550, EnrollmentOption::query()->sole()->entitlement_amount);
    }

    public function test_options_are_offered_by_licence_category(): void
    {
        $championship = Championship::factory()->create();
        EnrollmentOption::factory()->for($championship)->create(['title' => 'Free']);
        EnrollmentOption::factory()->for($championship)->create(['title' => 'Promo Easyrace', 'licence_categories' => [DriverLicence::EASYRACE->value]]);
        EnrollmentOption::factory()->for($championship)->create(['title' => 'Promo National', 'licence_categories' => [DriverLicence::LOCAL_NATIONAL->value, DriverLicence::LOCAL_INTERNATIONAL->value]]);
        EnrollmentOption::factory()->for($championship)->create(['title' => 'Old', 'active' => false]);

        $offered = fn (?DriverLicence $category) => $championship->enrollmentOptions()->offeredFor($category)->pluck('title')->all();

        $this->assertSame(['Free', 'Promo Easyrace'], $offered(DriverLicence::EASYRACE));
        $this->assertSame(['Free', 'Promo National'], $offered(DriverLicence::LOCAL_INTERNATIONAL));
        $this->assertSame(['Free'], $offered(null));
    }

    public function test_only_organizers_manage_options(): void
    {
        $championship = Championship::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('championships.enrollment-options.store', $championship), ['title' => 'Free', 'amount' => '0'])
            ->assertForbidden();
    }
}
