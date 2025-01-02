<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePaymentPathToDiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_path_replaced()
    {
        $payment = Payment::factory()->forParticipant()->create([
            'path' => 'payments/test.jpg',
        ]);

        $this->artisan('operations:process 2024_03_16_101941_update_payments_path_to_disk')
            ->assertSuccessful();

        $updatedPayment = $payment->fresh();

        $this->assertEquals('test.jpg', $updatedPayment->path);
    }

    public function test_payment_path_not_replaced_when_not_found()
    {
        $payment = Payment::factory()->forParticipant()->create([
            'path' => 'paymenttest.jpg',
        ]);

        $this->artisan('operations:process 2024_03_16_101941_update_payments_path_to_disk')
            ->assertSuccessful();

        $updatedPayment = $payment->fresh();

        $this->assertEquals('paymenttest.jpg', $updatedPayment->path);
    }

    public function test_payment_path_not_replaced()
    {
        $updatedAt = now()->subHour();
        $payment = Payment::factory()->forParticipant()->create([
            'path' => 'test.jpg',
            'updated_at' => $updatedAt,
        ]);

        $this->artisan('operations:process 2024_03_16_101941_update_payments_path_to_disk')
            ->assertSuccessful();

        $updatedPayment = $payment->fresh();

        $this->assertEquals('test.jpg', $updatedPayment->path);

        $this->assertTrue($updatedAt->isSameDay($updatedPayment->updated_at) && $updatedAt->isSameMinute($updatedPayment->updated_at));
    }
}
