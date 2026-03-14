<div class="inline-flex gap-2 items-center">
    <x-label for="paymentChannel" class="sr-only">{{ __('Payment Channel') }}</x-label>
    <select wire:model.live="paymentChannel" class="text-sm border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm">
        <option value="">{{ __('Select a channel...') }}</option>
        @foreach(\App\Models\PaymentChannelType::cases() as $channel)
            <option value="{{ $channel->value }}">{{ $channel->localizedName() }}</option>
        @endforeach
    </select>

    <x-input-error for="paymentChannel" />

    <div wire:dirty wire:target="paymentChannel">{{ __('Saving...') }}</div>

    <x-secondary-button type="button" wire:click="confirmPayment" class="inline-flex items-center gap-1 text-sm {{ $participant->payment_confirmed_at ? 'text-green-700 hover:text-green-900' : 'text-zinc-400 hover:text-zinc-700' }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 shrink-0">
            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
        </svg>
        @if ($participant->payment_confirmed_at)
            <x-time :value="$participant->payment_confirmed_at" />
        @else
            {{ __('Confirm payment') }}
        @endif
    </x-secondary-button>
</div>
