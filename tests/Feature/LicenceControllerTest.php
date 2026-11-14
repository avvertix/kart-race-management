<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceProvider;
use App\Models\Licence;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class LicenceControllerTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_drivers_cannot_list_or_verify_licences(): void
    {
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create();

        $this->actingAs($user)->get(route('licences.index'))->assertForbidden();
        $this->actingAs($user)->post(route('licences.verify', $licence))->assertForbidden();

        $this->assertFalse($licence->fresh()->isVerified());
    }

    public function test_organizer_filters_licences(): void
    {
        $organizer = User::factory()->organizer()->create();
        $year = (int) now()->format('Y');

        Licence::factory()->create(['holder' => LicenceFactory::holder('Giulia', 'Bianchi'), 'validity_year' => $year]);
        Licence::factory()->verified()->create(['holder' => LicenceFactory::holder('Luca', 'Verdi'), 'validity_year' => $year]);
        Licence::factory()->create([
            'holder' => LicenceFactory::holder('Anna', 'Neri'),
            'provider' => LicenceProvider::Asi,
            'licence_type' => 'ma',
            'validity_year' => $year - 1,
        ]);

        $this->actingAs($organizer)
            ->get(route('licences.index'))
            ->assertSuccessful()
            ->assertSee('Giulia Bianchi')
            ->assertSee('Luca Verdi')
            ->assertSee('Anna Neri');

        $this->actingAs($organizer)
            ->get(route('licences.index', ['unverified' => 1]))
            ->assertSee('Giulia Bianchi')
            ->assertSee('Anna Neri')
            ->assertDontSee('Luca Verdi');

        $this->actingAs($organizer)
            ->get(route('licences.index', ['provider' => 'asi']))
            ->assertSee('Anna Neri')
            ->assertDontSee('Giulia Bianchi');

        $this->actingAs($organizer)
            ->get(route('licences.index', ['year' => $year]))
            ->assertSee('Giulia Bianchi')
            ->assertDontSee('Anna Neri');
    }

    public function test_organizer_verifies_a_declared_licence(): void
    {
        $organizer = User::factory()->organizer()->create();
        $licence = Licence::factory()->create();

        $this->actingAs($organizer)
            ->get(route('licences.show', $licence))
            ->assertSuccessful()
            ->assertSee(route('licences.verify', $licence));

        $this->actingAs($organizer)
            ->post(route('licences.verify', $licence))
            ->assertRedirect(route('licences.show', $licence));

        $licence->refresh();

        $this->assertTrue($licence->isVerified());
        $this->assertEquals($organizer->getKey(), $licence->verified_by);

        $this->actingAs($organizer)
            ->post(route('licences.verify', $licence))
            ->assertForbidden();
    }
}
