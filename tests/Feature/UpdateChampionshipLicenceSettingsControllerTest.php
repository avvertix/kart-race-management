<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterParticipant;
use App\Models\Category;
use App\Models\Championship;
use App\Models\CompetitorLicence;
use App\Models\DriverLicence;
use App\Models\Race;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\TestCase;

class UpdateChampionshipLicenceSettingsControllerTest extends TestCase
{
    use CreateCompetitor;
    use CreateDriver;
    use CreateMechanic;
    use CreateVehicle;
    use FastRefreshDatabase;

    public function test_settings_form_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.licence-settings.edit', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_settings_form_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.licence-settings.edit', $championship));

        $response->assertForbidden();
    }

    public function test_settings_form_shown(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.licence-settings.edit', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('championship.licence-settings');
        $response->assertViewHas('championship', $championship);
    }

    public function test_accepted_driver_licences_saved(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.licence-settings.update', $championship), [
                'accepted_driver_licences' => [
                    DriverLicence::LOCAL_NATIONAL->value,
                    DriverLicence::LOCAL_INTERNATIONAL->value,
                ],
            ]);

        $response->assertRedirectToRoute('championships.show', $championship);

        $championship->refresh();

        $this->assertEquals(
            [DriverLicence::LOCAL_NATIONAL->value, DriverLicence::LOCAL_INTERNATIONAL->value],
            $championship->licences->accepted_driver_licences
        );
        $this->assertEmpty($championship->licences->accepted_competitor_licences);
    }

    public function test_accepted_competitor_licences_saved(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.licence-settings.update', $championship), [
                'accepted_competitor_licences' => [CompetitorLicence::LOCAL->value],
            ]);

        $response->assertRedirectToRoute('championships.show', $championship);

        $championship->refresh();

        $this->assertEmpty($championship->licences->accepted_driver_licences);
        $this->assertEquals(
            [CompetitorLicence::LOCAL->value],
            $championship->licences->accepted_competitor_licences
        );
    }

    public function test_all_licences_accepted_when_no_restrictions_set(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.licence-settings.update', $championship), []);

        $response->assertRedirectToRoute('championships.show', $championship);

        $championship->refresh();

        $this->assertEmpty($championship->licences->accepted_driver_licences);
        $this->assertEmpty($championship->licences->accepted_competitor_licences);
        $this->assertFalse($championship->licences->hasDriverLicenceRestriction());
        $this->assertFalse($championship->licences->hasCompetitorLicenceRestriction());
    }

    public function test_invalid_driver_licence_type_rejected(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.licence-settings.update', $championship), [
                'accepted_driver_licences' => [99],
            ]);

        $response->assertSessionHasErrors('accepted_driver_licences.0');
    }

    public function test_registration_blocked_for_disallowed_driver_licence(): void
    {
        config(['races.registration.form' => 'complete']);

        $championship = Championship::factory()->create([
            'licences' => ['accepted_driver_licences' => [DriverLicence::LOCAL_NATIONAL->value]],
        ]);

        $race = Race::factory()->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $this->expectException(ValidationException::class);

        app(RegisterParticipant::class)($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            'driver_licence_type' => DriverLicence::FOREIGN->value,
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);
    }

    public function test_registration_allowed_for_accepted_driver_licence(): void
    {
        config(['races.registration.form' => 'complete']);

        $championship = Championship::factory()->create([
            'licences' => ['accepted_driver_licences' => [DriverLicence::LOCAL_NATIONAL->value]],
        ]);

        $race = Race::factory()->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->assertNotNull($participant);
    }

    public function test_registration_allowed_when_no_driver_licence_restriction(): void
    {
        config(['races.registration.form' => 'complete']);

        $championship = Championship::factory()->create();
        $race = Race::factory()->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            'driver_licence_type' => DriverLicence::FOREIGN->value,
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->assertNotNull($participant);
    }
}
