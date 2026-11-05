<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\Orders\PlaceOrder;
use App\Data\EnrollmentSettingsData;
use App\Data\HolderData;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRole;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use App\Livewire\LicenceRequestReview;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class LicenceRequestYearTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_a_request_is_for_the_year_renewals_are_open_for(): void
    {
        $this->travelTo('2026-11-05 10:00');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('licence-requests.store'))->assertRedirect();

        $this->assertSame(2026, LicenceRequest::query()->sole()->validity_year);

        $this->travelTo('2026-12-02 10:00');

        $this->actingAs(User::factory()->create())->post(route('licence-requests.store'))->assertRedirect();

        $this->assertSame(2027, LicenceRequest::query()->latest('id')->first()->validity_year);
    }

    public function test_next_year_can_be_requested_while_a_championship_of_that_year_is_open_for_enrollment(): void
    {
        $this->travelTo('2026-11-05 10:00');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('licence-requests.store'), ['validity_year' => 2027])
            ->assertSessionHasErrors('validity_year');

        $this->assertSame(0, LicenceRequest::query()->count());

        $this->openChampionship(2027);

        $this->actingAs($user)
            ->post(route('licence-requests.store'), ['validity_year' => 2027])
            ->assertSessionHasNoErrors();

        $this->assertSame(2027, LicenceRequest::query()->sole()->validity_year);
    }

    public function test_only_driver_requests_of_the_championship_year_can_be_enrolled(): void
    {
        $this->travelTo('2026-11-05 10:00');
        $championship = $this->openChampionship(2027);
        $user = User::factory()->create();
        $lastYear = LicenceRequest::factory()->for($user)->submitted()->create(['validity_year' => 2026]);
        $mechanic = LicenceRequest::factory()->for($user)->submitted()->create(['validity_year' => 2027, 'licence_type' => 'mechanic']);
        $driver = LicenceRequest::factory()->for($user)->submitted()->create(['validity_year' => 2027]);

        $this->assertSame([$driver->getKey()], LicenceRequest::query()->enrollableIn($championship)->pluck('id')->all());

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$lastYear->ulid])
            ->assertSessionHasErrors();

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$mechanic->ulid])
            ->assertSessionHasErrors();

        $this->assertSame(0, $championship->enrollments()->count());
    }

    public function test_a_request_covered_by_a_package_is_for_the_year_of_the_championship_it_enrolls_in(): void
    {
        $this->travelTo('2026-11-05 10:00');
        $championship = $this->openChampionship(2027);
        $package = Product::factory()->create();
        Price::factory()->for($package)->create();
        $package->grants()->create(['kind' => ProductGrantKind::LicenceEmission]);
        $package->grants()->create(['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey()]);
        $user = User::factory()->create();
        $order = app(PlaceOrder::class)($user, $package);
        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        $this->actingAs($user)
            ->post(route('licence-requests.store'), ['order' => $order->ulid])
            ->assertSessionHasNoErrors();

        $this->assertSame(2027, LicenceRequest::query()->sole()->validity_year);
    }

    public function test_the_licence_is_emitted_for_the_year_of_the_request_its_enrollments_wait_for(): void
    {
        Notification::fake();
        $this->travelTo('2026-11-05 10:00');
        $championship = $this->openChampionship(2027);
        $licenceRequest = LicenceRequest::factory()->verified()->create(['validity_year' => 2027, 'payment_status' => LicenceRequestPaymentStatus::Covered]);
        Enrollment::factory()->for($championship)->waitingFor($licenceRequest)->create();
        $organizer = User::factory()->organizer()->create();

        Livewire::actingAs($organizer)
            ->test(LicenceRequestReview::class, ['licenceRequest' => $licenceRequest])
            ->assertSet('validityYear', '2027');

        try {
            app(EmitLicence::class)($licenceRequest, $organizer, 'A123456', 2026, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));
            $this->fail('A licence of another year was emitted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('validity_year', $exception->errors());
        }

        $licence = app(EmitLicence::class)($licenceRequest, $organizer, 'A123456', 2027, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        $this->assertSame(2027, $licence->validity_year);
    }

    public function test_a_request_nothing_waits_for_can_be_emitted_for_another_year(): void
    {
        Notification::fake();
        $this->travelTo('2026-11-05 10:00');
        $licenceRequest = LicenceRequest::factory()->verified()->create(['validity_year' => 2026, 'payment_status' => LicenceRequestPaymentStatus::Covered]);

        $licence = app(EmitLicence::class)($licenceRequest, User::factory()->organizer()->create(), 'A123456', 2027, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        $this->assertSame(2027, $licence->validity_year);
    }

    public function test_an_enrollment_with_a_licence_of_another_year_or_role_cannot_be_confirmed(): void
    {
        $this->travelTo('2026-11-05 10:00');
        $championship = $this->openChampionship(2027);
        $lastYear = Enrollment::factory()->for($championship)->create([
            'licence_id' => Licence::factory()->verified()->create(['validity_year' => 2026]),
        ]);
        $mechanic = Enrollment::factory()->for($championship)->create([
            'licence_id' => Licence::factory()->verified()->create(['validity_year' => 2027, 'role' => LicenceRole::Mechanic]),
        ]);

        $this->assertContains(__('The licence is not valid in the championship year.'), $lastYear->missingForConfirmation());
        $this->assertContains(__('The licence is not a driver licence.'), $mechanic->missingForConfirmation());
    }

    public function test_the_same_driver_cannot_be_enrolled_twice(): void
    {
        $this->travelTo('2026-11-05 10:00');
        $championship = $this->openChampionship(2026);
        $user = User::factory()->create();
        $holder = $this->holderWithFiscalCode();
        $licenceRequest = LicenceRequest::factory()->for($user)->submitted()->create(['validity_year' => 2026, 'holder' => $holder]);
        $otherRequest = LicenceRequest::factory()->for($user)->submitted()->create(['validity_year' => 2026, 'holder' => $holder]);
        $licence = Licence::factory()->for($user)->create(['holder' => $holder]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$licenceRequest->ulid])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid])
            ->assertSessionHasErrors('licence');

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$otherRequest->ulid])
            ->assertSessionHasErrors('licence');

        $this->assertSame(1, $championship->enrollments()->count());
    }

    public function test_a_licence_with_the_number_of_an_enrolled_one_cannot_be_enrolled(): void
    {
        $this->travelTo('2026-11-05 10:00');
        $championship = $this->openChampionship(2026);
        Enrollment::factory()->for($championship)->create([
            'licence_id' => Licence::factory()->verified()->create(['number' => 'A555001', 'holder' => LicenceFactory::holder('Anna', 'Verdi')]),
        ]);
        $user = User::factory()->create();
        $sameNumber = Licence::factory()->for($user)->create(['provider' => LicenceProvider::Asi, 'licence_type' => 'ma', 'number' => 'A555001', 'holder' => LicenceFactory::holder('Anna', 'Verdini')]);

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$sameNumber->ulid])
            ->assertSessionHasErrors('licence');
    }

    public function test_a_waiting_enrollment_of_an_already_enrolled_driver_is_rejected_on_emission(): void
    {
        Notification::fake();
        $this->travelTo('2026-11-05 10:00');
        $championship = $this->openChampionship(2026);
        $holder = $this->holderWithFiscalCode();
        $enrolled = Enrollment::factory()->for($championship)->create([
            'licence_id' => Licence::factory()->verified()->create(['holder' => $holder]),
        ]);
        $licenceRequest = LicenceRequest::factory()->verified()->create(['validity_year' => 2026, 'holder' => $holder, 'payment_status' => LicenceRequestPaymentStatus::Covered]);
        $waiting = Enrollment::factory()->for($championship)->waitingFor($licenceRequest)->create();

        app(EmitLicence::class)($licenceRequest, User::factory()->organizer()->create(), 'A123456', 2026, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        $this->assertSame(EnrollmentStatus::Submitted, $enrolled->fresh()->status);
        $this->assertSame(EnrollmentStatus::Rejected, $waiting->fresh()->status);
        $this->assertSame(__('The driver is already enrolled in the championship.'), $waiting->fresh()->rejection_reason);
    }

    private function openChampionship(int $year): Championship
    {
        $championship = Championship::factory()->create([
            'start_at' => now()->setDate($year, 3, 1)->startOfDay(),
            'end_at' => now()->setDate($year, 10, 31)->startOfDay(),
        ]);

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: now()->subDay(),
            closes_at: now()->addMonth(),
            requires_competitor_when_underage: false,
        );
        $championship->save();

        return $championship;
    }

    private function holderWithFiscalCode(): HolderData
    {
        $holder = LicenceFactory::holder('Luca', 'Bianchi');
        $holder->fiscal_code = 'BNCLCU08E14A794X';

        return $holder;
    }
}
