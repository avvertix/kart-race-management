<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\SubmitOrderPayment;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Price;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_drivers_cannot_manage_orders(): void
    {
        $order = $this->rangedOrder();

        $driver = $order->user;

        $this->actingAs($driver)->get(route('orders.index'))->assertForbidden();
        $this->actingAs($driver)->put(route('orders.price', $order), ['amount' => '1'])->assertForbidden();
        $this->actingAs($driver)->post(route('orders.confirm-payment', $order))->assertForbidden();
        $this->actingAs($driver)->post(route('orders.reject-payment', $order))->assertForbidden();

        $this->assertNull($order->fresh()->total);
    }

    public function test_organizer_filters_orders_by_status(): void
    {
        $organizer = User::factory()->organizer()->create();

        $ranged = $this->rangedOrder();
        $fixed = (new PlaceOrder)(User::factory()->create(['name' => 'Luigi Verdi']), Price::factory()->create()->product);

        $this->actingAs($organizer)
            ->get(route('orders.index'))
            ->assertSuccessful()
            ->assertSee('Mario Rossi')
            ->assertSee('Luigi Verdi');

        $this->actingAs($organizer)
            ->get(route('orders.index', ['status' => 'awaiting_price']))
            ->assertSuccessful()
            ->assertSee(route('orders.show', $ranged))
            ->assertDontSee(route('orders.show', $fixed));
    }

    public function test_organizer_sets_the_price_starting_from_the_lower_bound(): void
    {
        $organizer = User::factory()->organizer()->create();
        $order = $this->rangedOrder();

        $this->actingAs($organizer)
            ->get(route('orders.show', $order))
            ->assertSuccessful()
            ->assertSee('value="230.00"', false);

        $this->actingAs($organizer)
            ->put(route('orders.price', $order), ['amount' => '320'])
            ->assertSessionHasErrors('price_override_reason');

        $this->actingAs($organizer)
            ->put(route('orders.price', $order), ['amount' => '320', 'price_override_reason' => 'Includes insurance'])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertEquals(32000, $order->fresh()->total);
        $this->assertEquals(OrderStatus::AwaitingPayment, $order->fresh()->status);
    }

    public function test_organizer_previews_and_confirms_the_payment_proof(): void
    {
        Storage::fake('payments');

        $organizer = User::factory()->organizer()->create();
        $order = (new PlaceOrder)(User::factory()->create(), Price::factory()->create()->product);
        $payment = (new SubmitOrderPayment)($order, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        $this->actingAs($organizer)
            ->get(route('orders.show', $order))
            ->assertSuccessful()
            ->assertSee(route('payment-verification.show', $payment), false);

        $this->actingAs($organizer)
            ->post(route('orders.confirm-payment', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertEquals(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_organizer_rejects_the_payment_proof(): void
    {
        Storage::fake('payments');

        $organizer = User::factory()->organizer()->create();
        $order = (new PlaceOrder)(User::factory()->create(), Price::factory()->create()->product);
        (new SubmitOrderPayment)($order, UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'));

        $this->actingAs($organizer)
            ->post(route('orders.reject-payment', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertEquals(OrderStatus::AwaitingPayment, $order->fresh()->status);
    }

    private function rangedOrder(): Order
    {
        $price = Price::factory()->range(23000, 31000)->create();

        return (new PlaceOrder)(User::factory()->create(['name' => 'Mario Rossi']), $price->product);
    }
}
