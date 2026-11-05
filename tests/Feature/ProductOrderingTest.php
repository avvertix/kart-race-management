<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\GrantPackages;
use App\Enums\EntitlementMode;
use App\Enums\OrderStatus;
use App\Enums\ProductGrantKind;
use App\Models\Championship;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

/**
 * Ordering on the products page, until Products are removed (E5).
 */
class ProductOrderingTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

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
}
