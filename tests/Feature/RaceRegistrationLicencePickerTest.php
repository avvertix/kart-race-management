<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceRole;
use App\Features\LicencesAndProducts;
use App\Models\BibReservation;
use App\Models\Category;
use App\Models\CompetitorLicence;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Illuminate\Support\Facades\Notification;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class RaceRegistrationLicencePickerTest extends TestCase
{
    use CreateCompetitor;
    use CreateDriver;
    use CreateMechanic;
    use CreateVehicle;
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_a_guest_sees_the_form_as_before(): void
    {
        $race = $this->openRace();

        $this->get(route('races.registration.create', $race))
            ->assertSuccessful()
            ->assertDontSee(__('Fill in from your licences'));
    }

    public function test_the_pickers_list_the_licences_valid_in_the_race_year(): void
    {
        $race = $this->openRace();
        $user = User::factory()->create();
        Licence::factory()->for($user)->create(['number' => 'DRIVER-NOW', 'validity_year' => $race->event_start_at->year]);
        Licence::factory()->for($user)->create(['number' => 'DRIVER-OLD', 'validity_year' => $race->event_start_at->year - 1]);
        Licence::factory()->for($user)->create(['number' => 'COMPETITOR-NOW', 'includes_competitor' => true, 'validity_year' => $race->event_start_at->year]);
        Licence::factory()->for($user)->create(['number' => 'MECHANIC-NOW', 'role' => LicenceRole::Mechanic, 'race_licence_category' => null, 'validity_year' => $race->event_start_at->year]);
        Licence::factory()->create(['number' => 'SOMEONE-ELSE', 'validity_year' => $race->event_start_at->year]);

        $this->actingAs($user)
            ->get(route('races.registration.create', $race))
            ->assertSuccessful()
            ->assertSee(__('Fill in from your licences'))
            ->assertSee('DRIVER-NOW')
            ->assertSee('COMPETITOR-NOW')
            ->assertSee('MECHANIC-NOW')
            ->assertDontSee('DRIVER-OLD')
            ->assertDontSee('SOMEONE-ELSE');

        Feature::define(LicencesAndProducts::class, fn () => false);

        $this->actingAs(User::factory()->create())
            ->get(route('races.registration.create', $race))
            ->assertDontSee(__('Fill in from your licences'));
    }

    public function test_picking_licences_fills_the_form(): void
    {
        $race = $this->openRace();
        $user = User::factory()->create();
        $holder = LicenceFactory::holder('Mario', 'Rossi');
        $driver = Licence::factory()->for($user)->create(['number' => 'D-123', 'validity_year' => $race->event_start_at->year, 'holder' => $holder]);
        $competitor = Licence::factory()->for($user)->create(['number' => 'C-456', 'includes_competitor' => true, 'validity_year' => $race->event_start_at->year, 'holder' => LicenceFactory::holder('Paolo', 'Rossi')]);
        $mechanic = Licence::factory()->for($user)->create(['number' => 'M-789', 'role' => LicenceRole::Mechanic, 'race_licence_category' => null, 'validity_year' => $race->event_start_at->year, 'holder' => LicenceFactory::holder('Gino', 'Meccanico')]);

        $this->actingAs($user)
            ->get(route('races.registration.create', [
                $race,
                'driver_licence' => $driver->ulid,
                'competitor_licence' => $competitor->ulid,
                'mechanic_licence' => $mechanic->ulid,
            ]))
            ->assertSuccessful()
            ->assertSeeHtml('value="Mario"')
            ->assertSeeHtml('value="D-123"')
            ->assertSeeHtml('value="Bergamo"')
            ->assertSeeHtml('value="Paolo"')
            ->assertSeeHtml('value="C-456"')
            ->assertSeeHtml('value="Gino Meccanico"')
            ->assertSeeHtml('value="M-789"')
            ->assertSeeHtml('name="licence" value="'.$driver->ulid.'"');

        $this->actingAs(User::factory()->create())
            ->get(route('races.registration.create', [$race, 'driver_licence' => $driver->ulid]))
            ->assertSuccessful()
            ->assertDontSeeHtml('value="D-123"');
    }

    public function test_the_competitor_of_the_previous_registration_is_preselected(): void
    {
        $race = $this->openRace();
        $user = User::factory()->create();
        $driver = Licence::factory()->for($user)->create(['number' => 'D-PREV', 'validity_year' => $race->event_start_at->year]);
        $competitor = Licence::factory()->for($user)->create(['number' => 'C-PREV', 'includes_competitor' => true, 'validity_year' => $race->event_start_at->year, 'holder' => LicenceFactory::holder('Paolo', 'Rossi')]);
        Participant::factory()->recycle($race->championship)->create([
            'added_by' => $user->getKey(),
            'driver_licence' => Licence::hashNumber('D-PREV'),
            'competitor_licence' => Licence::hashNumber('C-PREV'),
        ]);

        $this->actingAs($user)
            ->get(route('races.registration.create', [$race, 'driver_licence' => $driver->ulid]))
            ->assertSeeHtml('value="'.$competitor->ulid.'" selected')
            ->assertSeeHtml('value="C-PREV"');

        $this->actingAs($user)
            ->get(route('races.registration.create', [$race, 'driver_licence' => $driver->ulid, 'competitor_licence' => 'none']))
            ->assertDontSeeHtml('value="C-PREV"');
    }

    public function test_the_reserved_number_and_competitor_type_are_filled(): void
    {
        $race = $this->openRace();
        $user = User::factory()->create();
        $driver = Licence::factory()->for($user)->create(['number' => 'D-RES', 'validity_year' => $race->event_start_at->year]);
        $competitor = Licence::factory()->for($user)->create(['number' => 'C-RES', 'includes_competitor' => true, 'validity_year' => $race->event_start_at->year]);
        BibReservation::factory()->recycle($race->championship)->withLicence('D-RES')->create(['bib' => 77]);

        $view = $this->actingAs($user)
            ->get(route('races.registration.create', [$race, 'driver_licence' => $driver->ulid, 'competitor_licence' => $competitor->ulid]))
            ->assertSuccessful()
            ->viewData('templateDriver');

        $this->assertSame(77, (int) $view->bib);
        $this->assertSame(CompetitorLicence::LOCAL->value, $view->competitor['licence_type']);
    }

    public function test_a_registration_from_a_picked_licence_records_it(): void
    {
        Notification::fake();

        $race = $this->openRace();
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create(['number' => 'D0001', 'validity_year' => $race->event_start_at->year]);
        $category = Category::factory()->recycle($race->championship)->create();

        $input = [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidCompetitor(),
            ...$this->generateValidMechanic(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ];

        $this->actingAs($user)
            ->post(route('races.registration.store', $race), [...$input, 'licence' => $licence->ulid])
            ->assertSessionHasNoErrors();

        $participant = Participant::query()->sole();

        $this->assertSame($licence->getKey(), $participant->licence_id);
        $this->assertSame(Licence::hashNumber('D0001'), $participant->driver_licence);

        $participant->forceDelete();

        $this->actingAs($user)
            ->post(route('races.registration.store', $race), [...$input, 'licence' => Licence::factory()->create(['number' => 'D0001-OTHER'])->ulid])
            ->assertSessionHasNoErrors();

        $this->assertNull(Participant::query()->sole()->licence_id);
    }

    private function openRace(): Race
    {
        $race = Race::factory()->create();

        $this->travelTo($race->registration_closes_at->subHour());

        return $race;
    }
}
