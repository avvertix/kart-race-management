<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\SetEnrollmentCompetitor;
use App\Actions\LicenceRequests\StartLicenceRenewal;
use App\Actions\PrepareRegistrationLicencePickers;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Livewire\LicenceRequestForm;
use App\Livewire\LicenceRequestReview;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Race;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class InstanceLicenceConfigurationTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('licence-documents');
    }

    public function test_licences_of_types_not_enabled_cannot_be_enrolled_renewed_or_name_the_competitor(): void
    {
        config(['licences.enabled_types' => ['asi.ma']]);

        $championship = Championship::factory()->create();
        $year = $championship->start_at->year;
        $user = User::factory()->create();

        Licence::factory()->for($user)->create(['provider' => LicenceProvider::Aci, 'licence_type' => 'naz-e', 'includes_competitor' => true, 'validity_year' => $year]);
        $asi = Licence::factory()->for($user)->create(['provider' => LicenceProvider::Asi, 'licence_type' => 'ma', 'validity_year' => $year]);
        Licence::factory()->for($user)->create(['provider' => LicenceProvider::Aci, 'licence_type' => 'naz-e', 'validity_year' => $year - 1]);
        $oldAsi = Licence::factory()->for($user)->create(['provider' => LicenceProvider::Asi, 'licence_type' => 'ma', 'validity_year' => $year - 1]);

        $this->assertEquals([$asi->getKey()], Licence::query()->enrollableIn($championship)->pluck('id')->all());
        $this->assertEquals([$oldAsi->getKey()], Licence::query()->renewableFor($championship)->pluck('id')->all());
        $this->assertTrue(Licence::query()->competitorIn($championship)->doesntExist());
    }

    public function test_requests_for_types_not_enabled_cannot_be_enrolled(): void
    {
        config(['licences.enabled_types' => ['asi.ma']]);

        $year = LicenceRequest::validityYearFor(now());
        $championship = Championship::factory()->create(['start_at' => now()->setDate($year, 1, 1)]);

        LicenceRequest::factory()->withLicenceAndHolder()->create(['provider' => LicenceProvider::Aci, 'licence_type' => 'naz-e', 'validity_year' => $year]);
        $asi = LicenceRequest::factory()->withLicenceAndHolder()->create(['provider' => LicenceProvider::Asi, 'licence_type' => 'ma', 'validity_year' => $year]);
        $notPicked = LicenceRequest::factory()->create(['validity_year' => $year]);

        $this->assertEqualsCanonicalizing([$asi->getKey(), $notPicked->getKey()], LicenceRequest::query()->enrollableIn($championship)->pluck('id')->all());
    }

    public function test_race_registration_does_not_offer_licences_of_types_not_enabled(): void
    {
        config(['licences.enabled_types' => ['asi.ma', 'asi.mechanic']]);

        $user = User::factory()->create();
        $race = Race::factory()->create();
        $year = $race->event_start_at->year;

        Licence::factory()->for($user)->create(['provider' => LicenceProvider::Aci, 'licence_type' => 'naz-e', 'includes_competitor' => true, 'validity_year' => $year]);
        $asi = Licence::factory()->for($user)->create(['provider' => LicenceProvider::Asi, 'licence_type' => 'ma', 'validity_year' => $year]);

        $pickers = app(PrepareRegistrationLicencePickers::class)($user, $race, []);

        $this->assertEquals([$asi->getKey()], $pickers['drivers']->modelKeys());
        $this->assertTrue($pickers['competitors']->isEmpty());
    }

    public function test_a_licence_of_a_type_not_enabled_cannot_be_named_competitor(): void
    {
        config(['licences.enabled_types' => ['asi.ma']]);

        $championship = Championship::factory()->create();
        $user = User::factory()->create();
        $enrollment = Enrollment::factory()->for($championship)->for($user)->create([
            'licence_id' => Licence::factory()->for($user)->create(['provider' => LicenceProvider::Asi, 'licence_type' => 'ma', 'validity_year' => $championship->start_at->year]),
        ]);
        $aci = Licence::factory()->for($user)->create(['provider' => LicenceProvider::Aci, 'licence_type' => 'naz-e', 'includes_competitor' => true, 'validity_year' => $championship->start_at->year]);

        $this->expectException(ValidationException::class);

        app(SetEnrollmentCompetitor::class)($enrollment, $aci->ulid, null);
    }

    public function test_renewing_a_licence_of_a_provider_not_offered_asks_for_the_provider_type_and_number_again(): void
    {
        config(['licences.enabled_types' => ['asi.ma']]);

        $user = User::factory()->create();
        $aci = Licence::factory()->for($user)->create(['provider' => LicenceProvider::Aci, 'licence_type' => 'naz-e', 'includes_competitor' => true]);

        $licenceRequest = app(StartLicenceRenewal::class)($user, $aci);

        $this->assertEquals(LicenceProvider::Asi, $licenceRequest->provider);
        $this->assertNull($licenceRequest->licence_type);
        $this->assertFalse($licenceRequest->includes_competitor);
        $this->assertNull($licenceRequest->previous_licence_number);
        $this->assertEquals($aci->holder->identityKey(), $licenceRequest->holder->identityKey());
    }

    public function test_a_type_not_enabled_cannot_be_requested_declared_or_submitted(): void
    {
        config(['licences.enabled_types' => ['asi.ma']]);

        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->set('provider', 'aci')
            ->set('licenceType', 'naz-e')
            ->set('previousLicenceNumber', '123456')
            ->call('saveLicence')
            ->assertHasErrors('provider');

        $this->actingAs($user)
            ->post(route('my-licences.store'), ['provider' => 'aci', 'licence_type' => 'naz-e'])
            ->assertSessionHasErrors('provider');

        $draft = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->for($user)->create(['provider' => LicenceProvider::Aci, 'licence_type' => 'naz-e']);

        $this->assertContains(LicenceRequestSection::Licence, $draft->missingSections());
    }

    public function test_championships_name_only_the_handled_providers(): void
    {
        config(['licences.enabled_types' => ['asi.ma']]);

        $organizer = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($organizer)
            ->get(route('championships.licence-settings.edit', $championship))
            ->assertSee('value="asi"', false)
            ->assertDontSee('value="aci"', false);

        $this->actingAs($organizer)
            ->put(route('championships.licence-settings.update', $championship), ['licence_provider' => 'aci'])
            ->assertSessionHasErrors('licence_provider');

        $this->actingAs($organizer)
            ->put(route('championships.licence-settings.update', $championship), ['licence_provider' => 'asi'])
            ->assertSessionHasNoErrors();

        $this->assertEquals(LicenceProvider::Asi, $championship->fresh()->licences->provider);
    }

    public function test_the_renewal_price_is_shown_before_submitting(): void
    {
        config(['licences.renewal_price' => '50']);

        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->for($user)->create();

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('openSection', 'review')
            ->assertSee('The renewal costs €50.00.');

        config(['licences.renewal_price' => '50-80']);

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest])
            ->assertSee('The renewal costs €50.00 – €80.00.');
    }

    public function test_a_renewal_covered_by_an_enrollment_shows_and_pre_fills_no_price(): void
    {
        config(['licences.renewal_price' => '50']);

        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->for($user)->create();
        $licenceRequest->coverWith(Enrollment::factory()->create());

        Livewire::actingAs($user)
            ->test(LicenceRequestForm::class, ['licenceRequest' => $licenceRequest->fresh()])
            ->assertDontSee('The renewal costs');

        $licenceRequest->update(['status' => LicenceRequestStatus::Submitted, 'submitted_at' => now()]);

        Livewire::actingAs(User::factory()->organizer()->create())
            ->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest->fresh()])
            ->assertSet('amount', '');
    }

    public function test_a_fixed_renewal_price_pre_fills_the_amount_at_verification(): void
    {
        config(['licences.renewal_price' => '50']);

        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->submitted()->create();

        Livewire::actingAs($organizer)
            ->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('amount', '50.00');

        config(['licences.renewal_price' => '50-80']);

        Livewire::actingAs($organizer)
            ->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('amount', '')
            ->assertSee('Renewal price: €50.00 – €80.00');
    }

    public function test_an_amount_already_set_is_kept_over_the_renewal_price(): void
    {
        config(['licences.renewal_price' => '50']);

        $organizer = User::factory()->organizer()->create();
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->verified()->awaitingPayment(7000)->create();

        Livewire::actingAs($organizer)
            ->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('amount', '70.00');
    }
}
