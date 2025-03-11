<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\PaymentSettingsData;
use App\Models\Championship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateChampionshipPaymentSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_details_can_be_saved()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('championships.payment.update', $championship), [
                'registration_price' => 10000,
                'bank' => 'Test Bank',
                'bank_account' => '123456789',
                'bank_holder' => 'Test Holder',
            ]);

        $response->assertRedirect(route('championships.show', $championship));
        $response->assertSessionHas('flash.banner', __(':championship cost and payment updated.', [
            'championship' => $championship->title,
        ]));

        $championship->refresh();

        $this->assertEquals(10000, $championship->registration_price);
        $this->assertInstanceOf(PaymentSettingsData::class, $championship->payment);
        $this->assertEquals('Test Bank', $championship->payment->bank_name);
        $this->assertEquals('123456789', $championship->payment->bank_account);
        $this->assertEquals('Test Holder', $championship->payment->bank_holder);
    }

    public function test_validation_errors_when_saving_payment_details()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.show', $championship))
            ->put(route('championships.payment.update', $championship), [
                'registration_price' => 'invalid',
                'bank' => '',
                'bank_account' => '',
                'bank_holder' => '',
            ]);

        $response->assertRedirect(route('championships.show', $championship));
        $response->assertSessionHasErrors(['registration_price', 'bank', 'bank_account']);
    }
}
