<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_fixed_price_order_awaits_payment(): void
    {
        $user = User::factory()->create();
        $price = Price::factory()->create(['amount' => 18000]);

        $order = (new PlaceOrder)($user, $price->product);

        $this->assertTrue($order->user->is($user));
        $this->assertEquals(OrderStatus::AwaitingPayment, $order->status);
        $this->assertEquals(18000, $order->total);

        $line = $order->lines->sole();
        $this->assertTrue($line->price->is($price));
        $this->assertEquals(18000, $line->amount);
        $this->assertEquals($price->product->title, $line->description);
    }

    public function test_ranged_price_order_awaits_price(): void
    {
        $user = User::factory()->create();
        $price = Price::factory()->range(23000, 31000)->create();

        $order = (new PlaceOrder)($user, $price->product);

        $this->assertEquals(OrderStatus::AwaitingPrice, $order->status);
        $this->assertNull($order->total);
        $this->assertNull($order->lines->sole()->amount);
    }

    public function test_order_keeps_early_bird_price_after_its_period_ends(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 10));

        $user = User::factory()->create();
        $product = Product::factory()->create();
        $earlyBird = Price::factory()->for($product)->create(['amount' => 15000, 'starts_at' => now()->setDate(2026, 11, 1), 'ends_at' => now()->setDate(2026, 11, 20)]);
        $standard = Price::factory()->for($product)->create(['amount' => 20000, 'starts_at' => now()->setDate(2026, 11, 21)]);

        $order = (new PlaceOrder)($user, $product);

        $this->travelTo(now()->setDate(2026, 12, 1));

        $order = $order->fresh();

        $this->assertTrue($product->fresh()->currentPrice()->is($standard));
        $this->assertTrue($order->lines->sole()->price->is($earlyBird));
        $this->assertEquals(15000, $order->total);
    }

    public function test_unverified_user_cannot_order(): void
    {
        $user = User::factory()->unverified()->create();
        $price = Price::factory()->create();

        $this->expectException(ValidationException::class);

        try {
            (new PlaceOrder)($user, $price->product);
        } finally {
            $this->assertDatabaseEmpty('orders');
        }
    }

    public function test_inactive_product_cannot_be_ordered(): void
    {
        $user = User::factory()->create();
        $price = Price::factory()->for(Product::factory()->inactive())->create();

        $this->expectException(ValidationException::class);

        (new PlaceOrder)($user, $price->product);
    }

    public function test_product_without_a_current_price_cannot_be_ordered(): void
    {
        $user = User::factory()->create();
        $price = Price::factory()->create(['starts_at' => now()->addWeek()]);

        $this->expectException(ValidationException::class);

        (new PlaceOrder)($user, $price->product);
    }
}
