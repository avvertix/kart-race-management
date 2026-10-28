<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterParticipant;
use App\Enums\LicenceLevel;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Enums\LicenceSource;
use App\Models\Category;
use App\Models\DriverLicence;
use App\Models\Licence;
use App\Models\LicenceGrade;
use App\Models\LicenceType;
use App\Models\Race;
use App\Models\User;
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

        $own = Licence::factory()->for($user)->create(['holder' => \Database\Factories\LicenceFactory::holder('Giulia', 'Bianchi')]);
        $other = Licence::factory()->create(['holder' => \Database\Factories\LicenceFactory::holder('Luca', 'Verdi')]);

        $this->actingAs($user)
            ->get(route('my-licences.index'))
            ->assertSuccessful()
            ->assertSee('Giulia Bianchi')
            ->assertDontSee('Luca Verdi')
            ->assertSee(route('my-licences.create'))
            ->assertSee(route('public.products.index'));

        $this->actingAs($user)->get(route('my-licences.show', $own))->assertSuccessful();
        $this->actingAs($user)->get(route('my-licences.show', $other))->assertForbidden();
        $this->actingAs($user)->get(route('my-licences.edit', $other))->assertForbidden();
        $this->actingAs($user)->delete(route('my-licences.destroy', $other))->assertForbidden();

        $this->assertModelExists($other);
    }

    public function test_user_declares_an_aci_licence(): void
    {
        $user = User::factory()->create();
        $licenceType = LicenceType::factory()->create();
        $grade = LicenceGrade::factory()->for($licenceType)->create(['level' => LicenceLevel::International, 'name' => 'International B']);

        $response = $this->actingAs($user)
            ->post(route('my-licences.store'), [
                'licence_type' => $licenceType->ulid,
                'level' => 'international',
                'licence_grade' => $grade->getKey(),
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
        $this->assertEquals(LicenceLevel::International, $licence->level);
        $this->assertEquals(DriverLicence::LOCAL_INTERNATIONAL, $licence->race_licence_category);
        $this->assertEquals('International B', $licence->gradeName());
        $this->assertFalse($licence->isVerified());
        $this->assertEquals('Giulia Bianchi', $licence->holder->fullName());
        $this->assertEquals('2010-05-14', $licence->holder->birth_date);
        $this->assertEquals('parent@example.com', $licence->holder->email);
        $this->assertEquals('Brescia', $licence->holder->residence_address->city);
    }

    public function test_asi_licence_takes_the_race_category_of_its_type(): void
    {
        $user = User::factory()->create();
        $licenceType = LicenceType::factory()->asi()->create();

        $this->actingAs($user)
            ->post(route('my-licences.store'), [
                'licence_type' => $licenceType->ulid,
                'grade_other' => 'Agonistica',
                'number' => 'ASI-12345',
                'validity_year' => now()->year,
                'holder' => $this->holderInput(),
            ])
            ->assertSessionHasNoErrors();

        $licence = Licence::sole();

        $this->assertEquals(LicenceProvider::Asi, $licence->provider);
        $this->assertNull($licence->level);
        $this->assertEquals(DriverLicence::LOCAL_NATIONAL, $licence->race_licence_category);
        $this->assertEquals('Agonistica', $licence->gradeName());
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
        $licenceType = LicenceType::factory()->create();

        Licence::factory()->create(['number' => '123456', 'validity_year' => now()->year, 'provider' => LicenceProvider::Aci]);

        $this->actingAs(User::factory()->create())
            ->post(route('my-licences.store'), [
                'licence_type' => $licenceType->ulid,
                'level' => 'national',
                'grade_other' => 'C Senior',
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
                'licence_type' => $licence->licenceType->ulid,
                'level' => 'national',
                'grade_other' => 'C Junior',
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
                'licence_type' => $licence->licenceType->ulid,
                'level' => 'national',
                'grade_other' => 'C Junior',
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
