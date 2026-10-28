<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\ConfirmOrderPayment;
use App\Actions\Orders\PlaceOrder;
use App\Actions\Orders\RejectOrderPayment;
use App\Actions\Orders\SetOrderPrice;
use App\Actions\Orders\SubmitOrderPayment;
use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Events\OrderPriced;
use App\Models\Order;
use App\Models\Price;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('payments');
    }

    public function test_organizer_sets_price_within_range_without_reason(): void
    {
        Event::fake([OrderPriced::class]);

        $organizer = User::factory()->organizer()->create();
        $order = $this->rangedOrder();

        (new SetOrderPrice)($order, $organizer, 25000);

        $order->refresh();

        $this->assertEquals(OrderStatus::AwaitingPayment, $order->status);
        $this->assertEquals(25000, $order->total);
        $this->assertEquals(25000, $order->lines->sole()->amount);
        $this->assertNull($order->price_override_reason);
        $this->assertEquals($organizer->getKey(), $order->priced_by);
        $this->assertNotNull($order->priced_at);

        Event::assertDispatched(OrderPriced::class, fn (OrderPriced $event) => $event->order->is($order));
    }

    public function test_price_outside_range_requires_a_reason(): void
    {
        $organizer = User::factory()->organizer()->create();
        $order = $this->rangedOrder();

        try {
            (new SetOrderPrice)($order, $organizer, 35000);
            $this->fail('A price outside the range without a reason was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('price_override_reason', $exception->errors());
        }

        $this->assertEquals(OrderStatus::AwaitingPrice, $order->fresh()->status);

        (new SetOrderPrice)($order, $organizer, 35000, 'International grade with insurance');

        $this->assertEquals(35000, $order->fresh()->total);
        $this->assertEquals('International grade with insurance', $order->fresh()->price_override_reason);
    }

    public function test_ranged_order_cannot_receive_a_proof_before_its_price_is_set(): void
    {
        $order = $this->rangedOrder();

        $this->expectException(ValidationException::class);

        try {
            (new SubmitOrderPayment)($order, $this->proof());
        } finally {
            $this->assertEquals(OrderStatus::AwaitingPrice, $order->fresh()->status);
            $this->assertEmpty($order->payments()->get());
        }
    }

    public function test_submitting_a_proof_stores_the_payment(): void
    {
        $order = $this->fixedOrder();

        $payment = (new SubmitOrderPayment)($order, $this->proof());

        $this->assertEquals(OrderStatus::PaymentSubmitted, $order->fresh()->status);
        $this->assertTrue($payment->payable->is($order));
        $this->assertNull($payment->participant_id);
        Storage::disk('payments')->assertExists($payment->path);
        $this->assertEquals(hash_file('sha512', Storage::disk('payments')->path($payment->path)), $payment->hash);
    }

    public function test_price_cannot_change_after_a_proof_is_uploaded(): void
    {
        $organizer = User::factory()->organizer()->create();
        $order = $this->fixedOrder();

        (new SubmitOrderPayment)($order, $this->proof());

        $this->expectException(ValidationException::class);

        try {
            (new SetOrderPrice)($order, $organizer, 10000, 'Discount');
        } finally {
            $this->assertEquals(18000, $order->fresh()->total);
        }
    }

    public function test_confirming_the_payment_marks_the_order_paid(): void
    {
        Event::fake([OrderPaid::class]);

        $organizer = User::factory()->organizer()->create();
        $order = $this->fixedOrder();

        (new SubmitOrderPayment)($order, $this->proof());

        (new ConfirmOrderPayment)($order, $organizer);

        $order->refresh();

        $this->assertEquals(OrderStatus::Paid, $order->status);
        $this->assertEquals($organizer->getKey(), $order->confirmed_by);
        $this->assertNotNull($order->paid_at);

        Event::assertDispatched(OrderPaid::class, fn (OrderPaid $event) => $event->order->is($order));
    }

    public function test_payment_cannot_be_confirmed_without_a_proof(): void
    {
        $order = $this->fixedOrder();

        $this->expectException(ValidationException::class);

        (new ConfirmOrderPayment)($order, User::factory()->organizer()->create());
    }

    public function test_rejecting_the_payment_awaits_a_new_proof(): void
    {
        $order = $this->fixedOrder();

        (new SubmitOrderPayment)($order, $this->proof());

        (new RejectOrderPayment)($order);

        $this->assertEquals(OrderStatus::AwaitingPayment, $order->fresh()->status);
        $this->assertCount(1, $order->payments()->get());

        (new SubmitOrderPayment)($order, $this->proof());

        $this->assertCount(2, $order->payments()->get());
    }

    public function test_price_cannot_change_after_a_rejected_proof(): void
    {
        $organizer = User::factory()->organizer()->create();
        $order = $this->fixedOrder();

        (new SubmitOrderPayment)($order, $this->proof());
        (new RejectOrderPayment)($order);

        $this->expectException(ValidationException::class);

        try {
            (new SetOrderPrice)($order, $organizer, 10000, 'Discount');
        } finally {
            $this->assertEquals(18000, $order->fresh()->total);
        }
    }

    public function test_order_with_a_proof_awaiting_confirmation_cannot_be_cancelled(): void
    {
        $order = $this->fixedOrder();

        (new SubmitOrderPayment)($order, $this->proof());

        $this->expectException(ValidationException::class);

        try {
            (new CancelOrder)($order);
        } finally {
            $this->assertEquals(OrderStatus::PaymentSubmitted, $order->fresh()->status);
        }
    }

    public function test_unpaid_order_can_be_cancelled(): void
    {
        $order = $this->rangedOrder();

        (new CancelOrder)($order);

        $this->assertEquals(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_paid_order_cannot_be_cancelled(): void
    {
        $order = $this->fixedOrder();

        (new SubmitOrderPayment)($order, $this->proof());
        (new ConfirmOrderPayment)($order, User::factory()->organizer()->create());

        $this->expectException(ValidationException::class);

        try {
            (new CancelOrder)($order);
        } finally {
            $this->assertEquals(OrderStatus::Paid, $order->fresh()->status);
        }
    }

    private function rangedOrder(): Order
    {
        $price = Price::factory()->range(23000, 31000)->create();

        return (new PlaceOrder)(User::factory()->create(), $price->product);
    }

    private function fixedOrder(): Order
    {
        $price = Price::factory()->create(['amount' => 18000]);

        return (new PlaceOrder)(User::factory()->create(), $price->product);
    }

    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');
    }
}
