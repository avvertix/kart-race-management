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


</div>
