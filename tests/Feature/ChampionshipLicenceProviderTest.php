<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceProvider;
use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class ChampionshipLicenceProviderTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['races.licence.provider' => 'ACI Sport']);
    }

    public function test_a_championship_without_provider_uses_the_platform_one(): void
    {
        $race = Race::factory()->national()->create();

        $this->assertNull($race->championship->licences->provider);
        $this->assertSame('ACI Sport', $race->championship->licenceProviderName());

        $this->travelTo($race->registration_closes_at->subHour());

        $this->get(route('races.registration.create', $race))
            ->assertSuccessful()
            ->assertSee('Licence issued by ACI Sport in');

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('races.show', $race))
            ->assertSee('Export verified drivers');
    }

    public function test_the_registration_uses_the_championship_provider(): void
    {
        $championship = Championship::factory()->create();
        $championship->licences->provider = LicenceProvider::Asi;
        $championship->save();

        $race = Race::factory()->national()->recycle($championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $this->get(route('races.registration.create', $race))
            ->assertSuccessful()
            ->assertSee('Licence issued by ASI in')
            ->assertDontSee('Licence issued by ACI Sport');
    }

    public function test_the_provider_is_chosen_when_creating_the_championship(): void
    {
        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('championships.store'), [
                'start' => today()->addMonth()->toDateString(),
                'end' => null,
                'title' => 'Provider championship',
                'description' => null,
                'licence_provider' => LicenceProvider::Asi->value,
            ])
            ->assertRedirect(route('championships.index'));

        $this->assertSame(LicenceProvider::Asi, Championship::query()->where('title', 'Provider championship')->sole()->licences->provider);
    }

    public function test_the_provider_is_changed_with_the_accepted_licences(): void
    {
        $championship = Championship::factory()->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('championships.licence-settings.edit', $championship))
            ->assertSee('Licence provider');

        $this->actingAs($organizer)
            ->put(route('championships.licence-settings.update', $championship), [
                'licence_provider' => LicenceProvider::Aci->value,
            ])
            ->assertRedirect(route('championships.show', $championship));

        $this->assertSame(LicenceProvider::Aci, $championship->fresh()->licences->provider);

        $this->actingAs($organizer)
            ->put(route('championships.licence-settings.update', $championship), [
                'licence_provider' => 'unknown',
            ])
            ->assertSessionHasErrors('licence_provider');
    }
}
