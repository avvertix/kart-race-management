<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceProvider;
use App\Models\Championship;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Race;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class LicenceProviderPerLicenceTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_aci_and_asi_licences_can_be_enrolled_whatever_the_championship_provider(): void
    {
        foreach ([null, LicenceProvider::Aci] as $provider) {
            $championship = $this->championship($provider);
            $user = User::factory()->create();
            $aci = Licence::factory()->for($user)->create(['holder' => LicenceFactory::holder('Luca', 'Bianchi')]);
            $asi = Licence::factory()->for($user)->create(['provider' => LicenceProvider::Asi, 'licence_type' => 'ma', 'holder' => LicenceFactory::holder('Anna', 'Neri')]);

            $this->actingAs($user)
                ->get(route('public.championships.enrollment', $championship))
                ->assertSee('value="licence:'.$aci->ulid.'"', false)
                ->assertSee('value="licence:'.$asi->ulid.'"', false);

            $this->actingAs($user)
                ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$asi->ulid])
                ->assertSessionHasNoErrors();
        }
    }

    public function test_renewing_a_licence_not_in_the_account_lets_the_driver_choose_the_provider(): void
    {
        $championship = $this->championship(LicenceProvider::Aci);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'renew:new'])
            ->assertSessionHasNoErrors();

        $this->assertCount(1, LicenceRequest::query()->where('user_id', $user->getKey())->whereNull('provider')->get());
    }

    public function test_race_numbers_can_be_copied_into_an_asi_championship(): void
    {
        $championship = $this->championship(LicenceProvider::Asi);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('championships.bib-reservations.copy', $championship))
            ->assertOk();
    }

    public function test_the_verified_drivers_export_is_offered_whatever_the_provider(): void
    {
        $organizer = User::factory()->organizer()->create();
        $race = Race::factory()->national()->recycle($this->championship(LicenceProvider::Asi))->create();

        $this->actingAs($organizer)
            ->get(route('races.show', $race))
            ->assertSee(route('races.export.verified-drivers', $race));

        $this->actingAs($organizer)
            ->get(route('races.export.verified-drivers', $race))
            ->assertOk();
    }

    private function championship(?LicenceProvider $provider): Championship
    {
        $championship = Championship::factory()->openForEnrollment()->create();
        $championship->licences->provider = $provider;
        $championship->save();

        return $championship;
    }
}
