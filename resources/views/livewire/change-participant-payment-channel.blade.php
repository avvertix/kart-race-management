<div class="inline-flex gap-2 items-center">
    <x-label for="paymentChannel" class="sr-only">{{ __('Payment Channel') }}</x-label>
    <flux:select size="sm" wire:model.live="paymentChannel" class="w-auto">
        <option value="">{{ __('Select a channel...') }}</option>
        @foreach(\App\Models\PaymentChannelType::cases() as $channel)
            <option value="{{ $channel->value }}">{{ $channel->localizedName() }}</option>
        @endforeach
    </flux:select>

    <x-input-error for="paymentChannel" />

    <div wire:dirty wire:target="paymentChannel">{{ __('Saving...') }}</div>

    <x-secondary-button type="button" icon="check" wire:click="confirmPayment" size="sm" class="{{ $participant->payment_confirmed_at ? 'text-green-700! hover:text-green-900!' : 'text-zinc-500! hover:text-zinc-800!' }}">
        @if ($participant->payment_confirmed_at)
            <x-time :value="$participant->payment_confirmed_at" />
        @else
            {{ __('Confirm payment') }}
        @endif
    </x-secondary-button>
</div>
