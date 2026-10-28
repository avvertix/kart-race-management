@php
    $order = $licenceRequest->order;
@endphp

@if ($order === null)
    <p class="text-sm text-zinc-600">{{ __('Choose a licence first: the order is created as soon as you pick one.') }}</p>
@else
    <dl class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
        <dt class="text-zinc-500">{{ __('Order') }}</dt>
        <dd class="col-span-2 font-mono break-all">{{ $order->ulid }}</dd>
        <dt class="text-zinc-500">{{ __('Amount') }}</dt>
        <dd class="col-span-2 font-mono tabular-nums">
            {{ $order->amountLabel() }}
            @if ($order->total === null)
                <span class="block font-sans text-zinc-600">{{ __('The organizer confirms the final price after checking your request. You can pay once it is set.') }}</span>
            @endif
        </dd>
        @if ($order->price_override_reason)
            <dt class="text-zinc-500">{{ __('Reason') }}</dt>
            <dd class="col-span-2">{{ $order->price_override_reason }}</dd>
        @endif
        <dt class="text-zinc-500">{{ __('Status') }}</dt>
        <dd class="col-span-2">{{ $order->status->label() }}</dd>
    </dl>

    @if ($order->status === \App\Enums\OrderStatus::AwaitingPayment)
        <div class="space-y-2 text-sm">
            <p>{{ __('Pay by bank transfer to:') }}</p>
            <p class="rounded-sm border border-zinc-200 bg-zinc-50 p-2">
                {{ config('races.organizer.bank_holder') }}
                <br>{{ config('races.organizer.bank') }}
                <br><span class="font-mono select-all break-all">{{ config('races.organizer.bank_account') }}</span>
            </p>
            <p>{{ __('Use the following text as the bank transfer reason:') }} <span class="font-mono select-all break-all">{{ $order->ulid }}</span></p>

            @if ($order->latestPayment)
                <p class="rounded-md border border-amber-400 bg-amber-50 px-2 py-1 text-amber-700">{{ __('The organizer could not confirm the previous payment proof. Upload a new one.') }}</p>
            @endif

            <div class="flex flex-wrap items-center gap-2">
                <input type="file" wire:model="proof" accept="image/png,image/jpeg,application/pdf" class="text-sm max-w-full" aria-label="{{ __('Payment proof') }}">
                <x-secondary-button type="button" wire:click="uploadProof" wire:loading.attr="disabled" wire:target="proof,uploadProof">{{ __('Upload payment proof') }}</x-secondary-button>
            </div>
            <x-input-error for="proof" />
        </div>
    @elseif ($order->status === \App\Enums\OrderStatus::PaymentSubmitted)
        <p class="text-sm text-zinc-600">{{ __('Payment proof uploaded. The organizer will confirm it.') }}</p>
    @elseif ($order->status === \App\Enums\OrderStatus::Paid)
        <p class="text-sm text-lime-700">{{ __('Payment confirmed by the organizer.') }}</p>
    @endif
@endif
