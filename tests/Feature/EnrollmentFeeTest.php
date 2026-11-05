<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\CreateEnrollment;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\GrantPackages;
use App\Data\EnrollmentSettingsData;
use App\Enums\EntitlementMode;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class EnrollmentFeeTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_enrollment_fee_is_a_fixed_amount(): void
    {
        $championship = Championship::factory()->create();
        $ranged = Product::factory()->enrollment()->recycle($championship)->create();
        Price::factory()->for($ranged)->range()->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->put(route('championships.enrollment-settings.update', $championship), [
                'enabled' => '1',
                'opens_at' => '2027-01-10T09:00',
                'closes_at' => '2027-02-28T23:59',
                'product' => $ranged->ulid,
            ])
            ->assertSessionHasErrors('product');

        $this->actingAs($organizer)
            ->post(route('products.store'), [
                'kind' => 'enrollment',
                'championship' => $championship->uuid,
                'title' => 'Enrollment',
                'prices' => [
                    ['type' => 'range', 'amount_min' => '100', 'amount_max' => '200', 'starts_on' => '2026-11-01'],
                ],
            ])
            ->assertSessionHasErrors('prices.0.type');
    }

    public function test_the_fee_can_cover_the_licence_renewal(): void
    {
        $championship = $this->championshipWithFee(coversRenewal: true);
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->submitted()->awaitingPayment()->create();

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee('It also covers the renewal of your licence');

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$licenceRequest->ulid])
            ->assertSessionHasNoErrors();

        $order = $championship->enrollments()->sole()->order;
        $licenceRequest = $licenceRequest->fresh();

        $this->assertNotNull($order);
        $this->assertSame(LicenceRequestPaymentStatus::Covered, $licenceRequest->payment_status);
        $this->assertTrue($licenceRequest->coveringOrder->is($order));
        $this->assertFalse($licenceRequest->isPaymentSettled(), 'The licence could be emitted before the fee is paid.');

        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        $this->assertTrue($licenceRequest->fresh()->isPaymentSettled());

        $order->update(['status' => OrderStatus::AwaitingPayment, 'paid_at' => null]);

        app(CancelOrder::class)($order);

        $licenceRequest = $licenceRequest->fresh();

        $this->assertSame(LicenceRequestPaymentStatus::AwaitingPayment, $licenceRequest->payment_status);
        $this->assertNull($licenceRequest->covering_order_id);
    }

    public function test_the_organizer_lets_the_fee_cover_the_renewal(): void
    {
        $championship = Championship::factory()->create();
        $fee = Product::factory()->enrollment()->recycle($championship)->create();
        Price::factory()->for($fee)->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('championships.enrollment-settings.update', $championship), [
                'enabled' => '1',
                'opens_at' => '2027-01-10T09:00',
                'closes_at' => '2027-02-28T23:59',
                'product' => $fee->ulid,
                'fee_covers_licence_renewal' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($championship->fresh()->enrollment->fee_covers_licence_renewal);
    }

    public function test_an_existing_range_price_makes_the_fee_unavailable(): void
    {
        $championship = $this->championshipWithFee(coversRenewal: false);
        $championship->enrollmentProduct()->prices()->update(['amount' => null, 'amount_min' => 10000, 'amount_max' => 20000]);
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee('Not available at the moment');

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid])
            ->assertSessionHasErrors('enrollment');

        $this->assertSame(0, $championship->enrollments()->count());
    }

    public function test_a_championship_without_enrollment_takes_no_packaged_enrollment(): void
    {
        $championship = Championship::factory()->create();
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        app(CreateEnrollment::class)($user, $championship, Licence::factory()->for($user)->create(), null, null, pays: false);
    }

    public function test_a_fee_not_covering_the_renewal_leaves_the_request_payment_alone(): void
    {
        $championship = $this->championshipWithFee(coversRenewal: false);
        $user = User::factory()->create();
        $licenceRequest = LicenceRequest::factory()->for($user)->submitted()->awaitingPayment()->create();

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$licenceRequest->ulid])
            ->assertSessionHasNoErrors();

        $this->assertSame(LicenceRequestPaymentStatus::AwaitingPayment, $licenceRequest->fresh()->payment_status);
    }

    public function test_enrollment_products_are_sold_only_while_enrolling(): void
    {
        $championship = $this->championshipWithFee(coversRenewal: false);
        $fee = $championship->enrollmentProduct();
        $renewalPackage = Product::factory()->create(['title' => 'Renewal and enrollment']);
        Price::factory()->for($renewalPackage)->create();
        $renewalPackage->grants()->create(['kind' => ProductGrantKind::LicenceEmission]);
        $renewalPackage->grants()->create(['kind' => ProductGrantKind::Enrollment, 'championship_id' => $championship->getKey()]);

        $this->get(route('public.products.index'))
            ->assertOk()
            ->assertDontSee($fee->title)
            ->assertSee('Renewal and enrollment');

        $this->actingAs(User::factory()->create())
            ->post(route('public.products.order', $fee))
            ->assertRedirect(route('public.championships.enrollment', $championship));

        $this->assertSame(0, Order::query()->count());
    }

    public function test_an_enrollment_grant_needs_a_championship_open_to_enrollment(): void
    {
        $package = Product::factory()->create();
        $closed = Championship::factory()->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('products.grants.store', $package), [
                'kind' => ProductGrantKind::Enrollment->value,
                'championship' => $closed->uuid,
            ])
            ->assertSessionHasErrors('championship');

        $this->assertSame(0, $package->grants()->count());
    }

    public function test_a_package_of_race_fee_discounts_is_ordered_for_a_licence(): void
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false, 'entitlements_enabled' => true]);
        $package = Product::factory()->create(['title' => 'Two races']);
        Price::factory()->for($package)->create();
        $package->grants()->create(['kind' => ProductGrantKind::Entitlement, 'championship_id' => $championship->getKey(), 'mode' => EntitlementMode::Credit, 'amount' => 2]);
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create();
        $notMine = Licence::factory()->create();

        $this->actingAs($user)
            ->get(route('public.products.show', $package))
            ->assertSee($licence->number)
            ->assertDontSee($notMine->number);

        $this->actingAs($user)
            ->post(route('public.products.order', $package))
            ->assertSessionHasErrors('licence');

        $this->actingAs($user)
            ->post(route('public.products.order', $package), ['licence' => $notMine->ulid])
            ->assertSessionHasErrors('licence');

        $this->actingAs($user)
            ->post(route('public.products.order', $package), ['licence' => $licence->ulid])
            ->assertSessionHasNoErrors();

        $order = Order::query()->sole();
        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        app(GrantPackages::class)($order);

        $this->assertSame(2, Entitlement::query()->where('licence_id', $licence->getKey())->sole()->remaining);
    }

    public function test_ordering_needs_the_permission(): void
    {
        $package = Product::factory()->create();
        Price::factory()->for($package)->create();

        $this->actingAs(User::factory()->timekeeper()->create())
            ->post(route('public.products.order', $package))
            ->assertForbidden();
    }

    private function championshipWithFee(bool $coversRenewal): Championship
    {
        $championship = Championship::factory()->create();
        $fee = Product::factory()->enrollment()->recycle($championship)->create(['title' => 'Season enrollment fee']);
        Price::factory()->for($fee)->create(['amount' => 18000]);

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: now()->subDay(),
            closes_at: now()->addMonth(),
            product_id: $fee->getKey(),
            requires_competitor_when_underage: false,
            fee_covers_licence_renewal: $coversRenewal,
        );
        $championship->save();

        return $championship;
    }
}
