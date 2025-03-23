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

    public function mount(Participant $participant)
    {
        $this->participant = $participant;
        $this->paymentChannel = $participant->payment_channel?->value;
    }

    public function updated($name, $value)
    {
        $this->validate();

        $this->participant->update(['payment_channel' => blank($value) ? null : PaymentChannelType::from($value)]);
    }

    public function render()
    {
        return view('livewire.change-participant-payment-channel');
    }

    protected function rules()
    {
        return [
            'paymentChannel' => ['nullable', Rule::enum(PaymentChannelType::class)],
        ];
    }
}
