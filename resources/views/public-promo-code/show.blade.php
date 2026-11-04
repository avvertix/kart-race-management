<x-app-layout>
    <x-slot name="title">
        {{ __('Promo code') }} &middot; {{ $promoCode->championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">{{ __('Promo code') }}</h2>
        <p class="text-sm text-zinc-500">{{ $promoCode->championship->title }}</p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl space-y-4 rounded-md border border-zinc-200 bg-white p-6">
            <p class="font-mono text-3xl font-bold tracking-widest text-zinc-900">{{ $promoCode->code }}</p>

            <div class="w-48" aria-hidden="true">{!! $promoCode->qrCodeSvg() !!}</div>

            <dl class="space-y-2">
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Championship') }}</dt>
                    <dd class="text-zinc-900">{{ $promoCode->championship->title }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Gives') }}</dt>
                    <dd class="text-zinc-900">{{ $promoCode->benefit() }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-zinc-500">{{ __('Status') }}</dt>
                    <dd class="text-zinc-900">{{ $promoCode->status->label() }}</dd>
                </div>
            </dl>

            @if ($promoCode->status === \App\Enums\PromoCodeStatus::Revoked)
                <p class="rounded-md border border-zinc-300 bg-zinc-50 p-3 text-sm text-zinc-700" role="status">{{ __('This code was revoked and can no longer be claimed.') }}</p>
            @elseif ($promoCode->status === \App\Enums\PromoCodeStatus::Unclaimed)
                <p class="text-sm text-zinc-600">{{ __('Claim it for one of your licences, or type it in a race registration of the championship.') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
