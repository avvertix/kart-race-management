<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\PlaceOrder;
use App\Enums\ProductKind;
use App\Models\Championship;
use App\Models\LicenceType;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_drivers_cannot_manage_products(): void
    {
        $user = User::factory()->create();

        $product = Product::factory()->create();

        $this->actingAs($user)->get(route('products.index'))->assertForbidden();
        $this->actingAs($user)->get(route('products.create'))->assertForbidden();
        $this->actingAs($user)->post(route('products.store'), ['kind' => 'package', 'title' => 'Package'])->assertForbidden();
        $this->actingAs($user)->get(route('products.edit', $product))->assertForbidden();
        $this->actingAs($user)->put(route('products.update', $product), ['title' => 'Changed'])->assertForbidden();

        $this->assertEquals(1, Product::count());
    }

    public function test_organizer_sees_products_and_their_forms(): void
    {
        $user = User::factory()->organizer()->create();

        $price = Price::factory()->range(23000, 31000)->create();

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertSuccessful()
            ->assertSee($price->product->title);

        $this->actingAs($user)
            ->get(route('products.create'))
            ->assertSuccessful();

        $this->actingAs($user)
            ->get(route('products.edit', $price->product))
            ->assertSuccessful()
            ->assertSee('230.00');
    }

    public function test_organizer_creates_licence_product_with_prices_in_euro(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-28 12:00', 'UTC'));

        $user = User::factory()->organizer()->create();
        $licenceType = LicenceType::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'kind' => 'licence_emission',
                'title' => 'Driver licence 2027',
                'description' => 'Emission or renewal',
                'licence_type' => $licenceType->ulid,
                'active' => '1',
                'prices' => [
                    ['type' => 'fixed', 'amount' => '150.50', 'starts_on' => '2026-11-01', 'ends_on' => '2026-11-30'],
                    ['type' => 'range', 'amount_min' => '230', 'amount_max' => '310', 'starts_on' => '2026-12-01'],
                ],
            ]);

        $response->assertRedirectToRoute('products.index');
        $response->assertSessionHasNoErrors();

        $product = Product::sole();

        $this->assertEquals(ProductKind::LicenceEmission, $product->kind);
        $this->assertTrue($product->licenceType->is($licenceType));
        $this->assertNull($product->championship_id);
        $this->assertTrue($product->active);

        [$earlyBird, $standard] = $product->prices;

        $this->assertEquals(15050, $earlyBird->amount);
        $this->assertEquals('2026-10-31 23:00:00', $earlyBird->starts_at->toDateTimeString(), 'Start of day in the races timezone');
        $this->assertEquals('2026-11-30 22:59:59', $earlyBird->ends_at->toDateTimeString(), 'End of day in the races timezone');

        $this->assertNull($standard->amount);
        $this->assertEquals(23000, $standard->amount_min);
        $this->assertEquals(31000, $standard->amount_max);
        $this->assertNull($standard->ends_at);
    }

    public function test_product_kind_decides_the_required_link(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'kind' => 'licence_emission',
                'title' => 'Driver licence',
                'championship' => (string) $championship->uuid,
            ])
            ->assertSessionHasErrors(['licence_type', 'championship']);

        $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'kind' => 'enrollment',
                'title' => 'Enrollment',
            ])
            ->assertSessionHasErrors(['championship']);

        $this->assertDatabaseEmpty('products');

        $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'kind' => 'enrollment',
                'title' => 'Enrollment',
                'championship' => (string) $championship->uuid,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Product::sole()->championship->is($championship));
    }

    public function test_price_is_either_fixed_or_a_valid_range(): void
    {
        $user = User::factory()->organizer()->create();
        $licenceType = LicenceType::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('products.store'), [
                'kind' => 'licence_emission',
                'title' => 'Driver licence',
                'licence_type' => $licenceType->ulid,
                'prices' => [
                    ['type' => 'fixed', 'starts_on' => '2026-11-01'],
                    ['type' => 'range', 'amount_min' => '310', 'amount_max' => '230', 'starts_on' => '2026-11-01'],
                    ['type' => 'fixed', 'amount' => '100', 'starts_on' => '2026-11-10', 'ends_on' => '2026-11-01'],
                ],
            ])
            ->assertSessionHasErrors(['prices.0.amount', 'prices.1.amount_max', 'prices.2.ends_on']);

        $this->assertDatabaseEmpty('products');
    }

    public function test_prices_used_by_orders_are_locked(): void
    {
        $user = User::factory()->organizer()->create();

        $price = Price::factory()->create(['amount' => 18000, 'starts_at' => now()->subDay()]);
        $product = $price->product;

        (new PlaceOrder)(User::factory()->create(), $product);

        $this
            ->actingAs($user)
            ->put(route('products.update', $product), [
                'title' => $product->title,
                'licence_type' => $product->licenceType->ulid,
                'prices' => [],
            ])
            ->assertSessionHasErrors('prices');

        $this->assertModelExists($price);

        $this
            ->actingAs($user)
            ->put(route('products.update', $product), [
                'title' => $product->title,
                'licence_type' => $product->licenceType->ulid,
                'prices' => [
                    ['id' => $price->getKey(), 'type' => 'fixed', 'amount' => '99', 'starts_on' => '2020-01-01', 'ends_on' => '2027-01-31'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $price->refresh();

        $this->assertEquals(18000, $price->amount);
        $this->assertEquals('2027-01-31', $price->ends_at->setTimezone(config('races.timezone'))->toDateString());
    }
}
