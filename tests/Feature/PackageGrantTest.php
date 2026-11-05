<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\LicenceRequests\EmitLicence;
use App\Actions\LicenceRequests\RejectLicenceRequest;
use App\Actions\LicenceRequests\SubmitLicenceRequest;
use App\Actions\Orders\ConfirmOrderPayment;
use App\Actions\Orders\GrantPackages;
use App\Actions\Orders\PlaceOrder;
use App\Data\EnrollmentSettingsData;
use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\EntitlementMode;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductGrant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class PackageGrantTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_the_organizer_adds_grants_to_a_package(): void
    {
        $organizer = User::factory()->organizer()->create();
        $championship = $this->championship();
        $package = Product::factory()->create();

        $this->actingAs($organizer)
            ->get(route('products.edit', $package))
            ->assertSee(__('What the package grants'));

        $this->actingAs($organizer)
            ->post(route('products.grants.store', $package), ['kind' => ProductGrantKind::LicenceEmission->value])
            ->assertRedirect(route('products.edit', $package));

        $this->actingAs($organizer)
            ->post(route('products.grants.store', $package), [
                'kind' => ProductGrantKind::Entitlement->value,
                'championship' => (string) $championship->uuid,
                'mode' => EntitlementMode::Credit->value,
                'amount' => '2',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($organizer)
            ->post(route('products.grants.store', $package), [
                'kind' => ProductGrantKind::Enrollment->value,
                'championship' => (string) $championship->uuid,
                'grants_bib_reservation' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, $package->grants()->count());

        $this->actingAs($organizer)
            ->post(route('products.grants.store', Product::factory()->enrollment()->create()), ['kind' => ProductGrantKind::LicenceEmission->value])
            ->assertNotFound();

        $this->actingAs($organizer)
            ->delete(route('product-grants.destroy', $package->grants()->first()))
            ->assertRedirect(route('products.edit', $package));

        $this->assertSame(2, $package->grants()->count());
    }

    public function test_a_paid_licence_package_starts_one_covered_renewal(): void
    {
        [$order, $user] = $this->paidPackageOrder([['kind' => ProductGrantKind::LicenceEmission]]);

        $this->actingAs($user)
            ->get(route('my-orders.show', $order))
            ->assertSee(__('Start your licence renewal'));

        $this->actingAs($user)
            ->post(route('licence-requests.store'), ['order' => $order->ulid])
            ->assertRedirect();

        $licenceRequest = LicenceRequest::query()->sole();

        $this->assertSame($order->getKey(), $licenceRequest->covering_order_id);
        $this->assertSame(LicenceRequestPaymentStatus::Covered, $licenceRequest->payment_status);

        $this->actingAs($user)
            ->post(route('licence-requests.store'), ['order' => $order->ulid])
            ->assertSessionHasErrors('order');

        $this->assertSame(1, LicenceRequest::query()->count());
    }

    public function test_a_full_package_enrolls_with_the_request_and_grants_on_emission(): void
    {
        $championship = $this->championship(grantsReservation: false);
        [$order, $user] = $this->paidPackageOrder([
            ['kind' => ProductGrantKind::LicenceEmission],
            ['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey(), 'grants_bib_reservation' => true],
            ['kind' => ProductGrantKind::Entitlement, 'championship_id' => $championship->getKey(), 'mode' => EntitlementMode::Credit, 'amount' => 2],
        ]);

        $licenceRequest = $this->submittedCoveredRequest($user, $order);

        $enrollment = $championship->enrollments()->sole();

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->status);
        $this->assertTrue($enrollment->licenceRequest->is($licenceRequest));
        $this->assertSame($order->getKey(), $enrollment->order_id);
        $this->assertTrue($user->can('pickRaceNumber', $enrollment));

        app(PickRaceNumber::class)($enrollment, 33, $user);
        $this->assertSame(0, Entitlement::query()->count());

        $organizer = User::factory()->organizer()->create();
        $licenceRequest->update(['status' => LicenceRequestStatus::Verified, 'verified_at' => now()]);
        $licence = app(EmitLicence::class)($licenceRequest->fresh(), $organizer, 'PKG-1', $championship->start_at->year, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        $entitlement = Entitlement::query()->sole();
        $this->assertSame($licence->getKey(), $entitlement->licence_id);
        $this->assertSame(2, $entitlement->remaining);
        $this->assertTrue($enrollment->fresh()->licence->is($licence));

        app(GrantPackages::class)($order->fresh());
        $this->assertSame(1, Entitlement::query()->count());
    }

    public function test_an_enrollment_package_paid_after_emission_grants_on_payment(): void
    {
        $championship = $this->championship();
        $package = Product::factory()->create();
        Price::factory()->for($package)->create();
        ProductGrant::factory()->for($package)->create(['championship_id' => $championship->getKey(), 'amount' => 1]);

        $licenceRequest = LicenceRequest::factory()->verified()->create(['payment_status' => LicenceRequestPaymentStatus::Covered]);
        $order = app(PlaceOrder::class)($licenceRequest->user, $package);
        $championship->enrollments()->create([
            'licence_request_id' => $licenceRequest->getKey(),
            'user_id' => $licenceRequest->user_id,
            'status' => EnrollmentStatus::Submitted,
            'order_id' => $order->getKey(),
        ]);

        app(EmitLicence::class)($licenceRequest, User::factory()->organizer()->create(), 'A777001', $championship->start_at->year, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));
        $this->assertSame(0, Entitlement::query()->count());

        $order->update(['status' => OrderStatus::PaymentSubmitted]);
        app(ConfirmOrderPayment::class)($order->fresh(), User::factory()->organizer()->create());

        $this->assertSame(Licence::hashNumber('A777001'), Entitlement::query()->sole()->licence_hash);
    }

    public function test_an_enrollment_package_is_ordered_only_when_enrolling(): void
    {
        $championship = $this->championship();
        $package = Product::factory()->create(['title' => 'Enrollment only package']);
        Price::factory()->for($package)->create();
        ProductGrant::factory()->for($package)->create(['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey(), 'mode' => null, 'amount' => null]);
        $renewalPackage = Product::factory()->create(['title' => 'Renewal and enrollment']);
        Price::factory()->for($renewalPackage)->create();
        ProductGrant::factory()->for($renewalPackage)->create(['kind' => ProductGrantKind::LicenceEmission, 'championship_id' => null, 'mode' => null, 'amount' => null]);
        ProductGrant::factory()->for($renewalPackage)->create(['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey(), 'mode' => null, 'amount' => null]);
        $user = User::factory()->create();
        Licence::factory()->for($user)->create();

        $this->actingAs($user)
            ->post(route('public.products.order', $package))
            ->assertRedirect(route('public.championships.enrollment', $championship));

        $this->assertSame(0, Order::query()->count());

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee('Enrollment only package')
            ->assertSee('Renewal and enrollment');
    }

    public function test_a_rejected_packaged_request_leaves_the_enrollment_waiting_and_grants_nothing(): void
    {
        $championship = $this->championship();
        [$order, $user] = $this->paidPackageOrder([
            ['kind' => ProductGrantKind::LicenceEmission],
            ['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey(), 'grants_bib_reservation' => true],
            ['kind' => ProductGrantKind::Entitlement, 'championship_id' => $championship->getKey(), 'mode' => EntitlementMode::Credit, 'amount' => 1],
        ]);

        $licenceRequest = $this->submittedCoveredRequest($user, $order);
        $enrollment = $championship->enrollments()->sole();
        $reservation = app(PickRaceNumber::class)($enrollment, 44, $user);

        app(RejectLicenceRequest::class)($licenceRequest->fresh(), User::factory()->organizer()->create(), 'Not eligible.');

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);
        $this->assertTrue($enrollment->fresh()->needsLicence());
        $this->assertSame(BibReservationStatus::Pending, $reservation->fresh()->status);
        $this->assertTrue($order->fresh()->canStartLicenceRenewal());
        $this->assertSame(0, Entitlement::query()->count());
    }

    public function test_an_enrollment_package_grants_once_paid_and_with_a_licence(): void
    {
        $championship = $this->championship();
        $package = Product::factory()->create(['title' => 'Enrollment and two races']);
        Price::factory()->for($package)->create();
        $package->grants()->create(['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey()]);
        $package->grants()->create(['kind' => ProductGrantKind::Entitlement, 'championship_id' => $championship->getKey(), 'mode' => EntitlementMode::Credit, 'amount' => 2]);

        $driver = User::factory()->create();
        $licence = Licence::factory()->for($driver)->create();

        $this->actingAs($driver)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee('Enrollment and two races');

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid, 'product' => $package->ulid])
            ->assertSessionHasNoErrors();

        $order = $championship->enrollments()->sole()->order;
        $this->assertTrue($order->lines->first()->product->is($package));

        $order->update(['status' => OrderStatus::PaymentSubmitted]);
        app(ConfirmOrderPayment::class)($order, User::factory()->organizer()->create());

        $entitlement = Entitlement::query()->sole();
        $this->assertSame($licence->getKey(), $entitlement->licence_id);
        $this->assertSame($licence->number_hash, $entitlement->licence_hash);
    }

    /**
     * @param  array<int, array<string, mixed>>  $grants
     * @return array{0: Order, 1: User}
     */
    private function paidPackageOrder(array $grants): array
    {
        $package = Product::factory()->create();
        Price::factory()->for($package)->create();

        foreach ($grants as $grant) {
            $package->grants()->create($grant);
        }

        $user = User::factory()->create();
        $order = app(PlaceOrder::class)($user, $package);
        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        return [$order->fresh(), $user];
    }

    private function submittedCoveredRequest(User $user, Order $order): LicenceRequest
    {
        $licenceRequest = LicenceRequest::factory()->withLicenceAndHolder()->withAllDocuments()->create([
            'user_id' => $user->getKey(),
            'covering_order_id' => $order->getKey(),
            'payment_status' => LicenceRequestPaymentStatus::Covered,
        ]);

        return app(SubmitLicenceRequest::class)($licenceRequest);
    }

    private function championship(bool $grantsReservation = true): Championship
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: now()->subDay(),
            closes_at: now()->addMonth(),
            grants_bib_reservation: $grantsReservation,
            requires_competitor_when_underage: false,
        );
        $championship->save();

        return $championship;
    }
}
