<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterParticipant;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Enums\LicenceSource;
use App\Models\Category;
use App\Models\DriverLicence;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Race;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\CreateDriver;
use Tests\CreateVehicle;
use Tests\TestCase;

class MyLicenceControllerTest extends TestCase
{
    use CreateDriver;
    use CreateVehicle;
    use FastRefreshDatabase;

    public function test_user_sees_only_the_licences_they_manage(): void
    {
        $user = User::factory()->create();

        $own = Licence::factory()->for($user)->create(['holder' => LicenceFactory::holder('Giulia', 'Bianchi')]);
        $other = Licence::factory()->create(['holder' => LicenceFactory::holder('Luca', 'Verdi')]);

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSuccessful()
            ->assertSee('Giulia Bianchi')
            ->assertDontSee('Luca Verdi')
            ->assertSee(route('my-licences.create'))
            ->assertSee(route('licence-requests.store'));

        $this->actingAs($user)->get(route('my-licences.show', $own))->assertSuccessful();
        $this->actingAs($user)->get(route('my-licences.show', $other))->assertForbidden();
        $this->actingAs($user)->get(route('my-licences.edit', $other))->assertForbidden();
        $this->actingAs($user)->delete(route('my-licences.destroy', $other))->assertForbidden();

        $this->assertModelExists($other);
    }

    public function test_user_declares_an_aci_licence(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('my-licences.store'), [
                'provider' => 'aci',
                'licence_type' => 'int-e',
                'number' => '123456',
                'validity_year' => now()->year,
                'holder' => $this->holderInput(),
            ]);

        $licence = Licence::sole();

        $response->assertRedirect(route('my-licences.show', $licence));
        $response->assertSessionHasNoErrors();

        $this->assertTrue($licence->user->is($user));
        $this->assertEquals(LicenceSource::Declared, $licence->source);
        $this->assertEquals(LicenceProvider::Aci, $licence->provider);
        $this->assertEquals(LicenceRole::Driver, $licence->role);
        $this->assertEquals('int-e', $licence->licence_type);
        $this->assertTrue($licence->includes_competitor);
        $this->assertEquals(DriverLicence::LOCAL_INTERNATIONAL, $licence->race_licence_category);
        $this->assertEquals('INT E with competitor licence', $licence->licenceTypeLabel());
        $this->assertFalse($licence->isVerified());
        $this->assertEquals('Giulia Bianchi', $licence->holder->fullName());
        $this->assertEquals('2010-05-14', $licence->holder->birth_date);
        $this->assertEquals('parent@example.com', $licence->holder->email);
        $this->assertEquals('Brescia', $licence->holder->residence_address->city);
    }

    public function test_asi_licence_never_includes_the_competitor_licence(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('my-licences.store'), [
                'provider' => 'asi',
                'licence_type' => 'driver',
                'number' => 'ASI-12345',
                'validity_year' => now()->year,
                'holder' => $this->holderInput(),
            ])
            ->assertSessionHasNoErrors();

        $licence = Licence::sole();

        $this->assertEquals(LicenceProvider::Asi, $licence->provider);
        $this->assertFalse($licence->includes_competitor);
        $this->assertEquals(DriverLicence::LOCAL_NATIONAL, $licence->race_licence_category);
    }

    public function test_aci_licence_for_the_driver_only_excludes_the_competitor_licence(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('my-licences.store'), [
                'provider' => 'aci',
                'licence_type' => 'naz-e',
                'driver_only' => '1',
                'number' => '123456',
                'validity_year' => now()->year,
                'holder' => $this->holderInput(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse(Licence::sole()->includes_competitor);
    }

    public function test_only_listed_licence_types_can_be_declared(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('my-licences.store'), [
                'provider' => 'asi',
                'licence_type' => 'naz-e',
                'number' => 'ASI-12345',
                'validity_year' => now()->year,
                'holder' => $this->holderInput(),
            ])
            ->assertSessionHasErrors('licence_type');

        $this->assertDatabaseEmpty('licences');
    }

    public function test_renew_is_offered_on_the_latest_licence_of_each_holder(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 5));

        $user = User::factory()->create();
        $giulia = LicenceFactory::holder('Giulia', 'Bianchi');

        $older = Licence::factory()->for($user)->create(['holder' => $giulia, 'validity_year' => 2025]);
        $latest = Licence::factory()->for($user)->create(['holder' => $giulia, 'validity_year' => 2026]);
        $renewed = Licence::factory()->for($user)->create(['holder' => LicenceFactory::holder('Luca', 'Verdi'), 'validity_year' => 2027]);
        $beingRenewed = Licence::factory()->for($user)->create(['holder' => LicenceFactory::holder('Anna', 'Neri'), 'validity_year' => 2026]);

        LicenceRequest::factory()->for($user)->create(['previous_licence_number' => $beingRenewed->number]);

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSuccessful()
            ->assertSee('Renew for 2027')
            ->assertSee('value="'.$latest->ulid.'"', false)
            ->assertDontSee('value="'.$older->ulid.'"', false)
            ->assertDontSee('value="'.$renewed->ulid.'"', false)
            ->assertDontSee('value="'.$beingRenewed->ulid.'"', false);
    }

    public function test_a_licence_keeps_its_type_name_once_the_type_is_no_longer_offered(): void
    {
        config(['licences.types.aci.naz-c' => ['name' => 'NAZ C', 'role' => LicenceRole::Driver, 'active' => false]]);

        $user = User::factory()->create();
        Licence::factory()->for($user)->create(['licence_type' => 'naz-c']);

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSuccessful()
            ->assertSee('NAZ C');
    }

    public function test_renew_starts_a_pre_filled_request(): void
    {
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create(['number' => 'A777']);

        $response = $this->actingAs($user)
            ->post(route('licence-requests.store'), ['licence' => $licence->ulid]);

        $licenceRequest = LicenceRequest::sole();

        $response->assertRedirect(route('licence-requests.edit', $licenceRequest));
        $this->assertEquals('A777', $licenceRequest->previous_licence_number);

        $this->actingAs(User::factory()->create())
            ->post(route('licence-requests.store'), ['licence' => $licence->ulid])
            ->assertNotFound();

        $this->assertEquals(1, LicenceRequest::count());
    }

    public function test_number_and_holder_are_encrypted_and_the_hash_matches_race_registration(): void
    {
        Notification::fake();

        $licence = Licence::factory()->create(['number' => 'D0001']);

        $race = Race::factory()->create();
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $row = DB::table('licences')->where('id', $licence->getKey())->first();

        $this->assertStringNotContainsString('D0001', $row->number);
        $this->assertStringNotContainsString('Rossi', $row->holder);
        $this->assertEquals('D0001', $licence->fresh()->number);
        $this->assertEquals($participant->driver_licence, $row->number_hash);
    }

    public function test_the_same_number_and_year_cannot_be_declared_twice(): void
    {
        Licence::factory()->create(['number' => '123456', 'validity_year' => now()->year, 'provider' => LicenceProvider::Aci]);

        $this->actingAs(User::factory()->create())
            ->post(route('my-licences.store'), [
                'provider' => 'aci',
                'licence_type' => 'naz-e',
                'number' => '123456',
                'validity_year' => now()->year,
                'holder' => $this->holderInput(),
            ])
            ->assertSessionHasErrors('number');

        $this->assertEquals(1, Licence::count());
    }

    public function test_user_edits_and_removes_a_declared_licence_before_verification(): void
    {
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create(['validity_year' => now()->year]);

        $this->actingAs($user)
            ->put(route('my-licences.update', $licence), [
                'provider' => 'aci',
                'licence_type' => 'naz-e',
                'number' => '654321',
                'validity_year' => now()->year + 1,
                'holder' => $this->holderInput(),
            ])
            ->assertRedirect(route('my-licences.show', $licence))
            ->assertSessionHasNoErrors();

        $licence->refresh();

        $this->assertEquals('654321', $licence->number);
        $this->assertEquals(Licence::hashNumber('654321'), $licence->number_hash);
        $this->assertEquals(now()->year + 1, $licence->validity_year);

        $this->actingAs($user)
            ->delete(route('my-licences.destroy', $licence))
            ->assertRedirect(route('my-licences.index'));

        $this->assertModelMissing($licence);
    }

    public function test_verified_licences_cannot_be_changed_by_the_user(): void
    {
        $user = User::factory()->create();
        $licence = Licence::factory()->verified()->for($user)->create(['number' => '123456']);

        $this->actingAs($user)->get(route('my-licences.edit', $licence))->assertForbidden();

        $this->actingAs($user)
            ->put(route('my-licences.update', $licence), [
                'provider' => 'aci',
                'licence_type' => 'naz-e',
                'number' => '654321',
                'validity_year' => now()->year,
                'holder' => $this->holderInput(),
            ])
            ->assertForbidden();

        $this->actingAs($user)->delete(route('my-licences.destroy', $licence))->assertForbidden();

        $this->assertEquals('123456', $licence->fresh()->number);
    }

    public function test_temporary_licence_needs_a_signed_link_and_the_manager(): void
    {
        Storage::fake('licence-documents');
        Storage::disk('licence-documents')->put('temporary-licences/licence.pdf', '%PDF-1.4');

        $user = User::factory()->create();
        $licence = Licence::factory()->emitted()->for($user)->create();

        $signedUrl = URL::temporarySignedRoute('my-licences.temporary-licence', now()->addMinutes(15), ['licence' => $licence]);

        $this->actingAs($user)->get(route('my-licences.temporary-licence', $licence))->assertForbidden();
        $this->actingAs($user)->get($signedUrl)->assertSuccessful();
        $this->actingAs(User::factory()->create())->get($signedUrl)->assertForbidden();
    }

    /**
     * @return array<string, mixed>
     */
    private function holderInput(): array
    {
        return [
            'first_name' => 'Giulia',
            'last_name' => 'Bianchi',
            'sex' => '20',
            'birth_date' => '14/05/2010',
            'birth_place' => 'Brescia',
            'fiscal_code' => '',
            'nationality' => 'Italian',
            'email' => 'Parent@Example.com',
            'phone' => '+39 333 7654321',
            'residence_address' => [
                'address' => 'Via Milano 3',
                'city' => 'Brescia',
                'province' => 'BS',
                'postal_code' => '25121',
            ],
        ];
    }
}
