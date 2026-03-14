<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Participant;
use App\Models\PaymentChannelType;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ChangeParticipantPaymentChannel extends Component
{
    #[Locked]
    public Participant $participant;

    #[Validate()]
    public ?int $paymentChannel;

    public function mount(Participant $participant): void
    {
        $this->participant = $participant;
        $this->paymentChannel = $participant->payment_channel?->value;
    }

    public function confirmPayment(): void
    {
        $this->participant->update([
            'payment_confirmed_at' => $this->participant->payment_confirmed_at ? null : now(),
        ]);
    }

    public function updated($name, $value): void
    {
        $this->validate();

        $channel = blank($value) ? null : PaymentChannelType::from((int) $value);

        $confirmedAt = match ($channel) {
            PaymentChannelType::CASH, PaymentChannelType::CREDIT_CARD => $this->participant->payment_confirmed_at ?? now(),
            default => $this->participant->payment_confirmed_at ?? null,
        };

        $this->participant->update([
            'payment_channel' => $channel,
            'payment_confirmed_at' => $confirmedAt,
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.change-participant-payment-channel');
    }

    protected function rules(): array
    {
        return [
            'paymentChannel' => ['nullable', Rule::enum(PaymentChannelType::class)],
        ];
    }
}
