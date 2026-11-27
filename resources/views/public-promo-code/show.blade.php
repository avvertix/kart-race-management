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
                @if ($promoCode->entitlement)
                    <div>
                        <dt class="text-sm text-zinc-500">{{ __('Used') }}</dt>
                        <dd class="text-zinc-900">{{ __(':used of :amount used', ['used' => $promoCode->entitlement->mode === \App\Enums\EntitlementMode::Credit ? $promoCode->entitlement->used() : \App\Models\Currency::EUR->format($promoCode->entitlement->used()), 'amount' => $promoCode->benefit()]) }}</dd>
                    </div>
                @endif
            </dl>

            @if ($promoCode->status === \App\Enums\PromoCodeStatus::Revoked)
                <p class="rounded-md border border-zinc-300 bg-zinc-50 p-3 text-sm text-zinc-700" role="status">{{ __('This code was revoked and can no longer be claimed.') }}</p>
            @elseif ($promoCode->status === \App\Enums\PromoCodeStatus::Unclaimed)
                <p class="text-sm text-zinc-600">{{ __('Claim it for one of your licences, or type it in a race registration of the championship.') }}</p>

                @auth
                    <form method="POST" action="{{ route('promo-codes.claim', $promoCode) }}" class="space-y-3">
                        @csrf
                        <x-validation-errors />
                        @if ($claimableLicences->isEmpty())
                            <p class="text-sm text-zinc-600">{{ __('You have no driver licence valid for :year to claim it for.', ['year' => $promoCode->championship->start_at->year]) }}</p>
                        @else
                            <fieldset class="space-y-2">
                                <legend class="text-sm font-medium text-zinc-900">{{ __('Claim it for') }}</legend>
                                @foreach ($claimableLicences as $licence)
                                    <label class="flex items-start gap-3">
                                        <input type="radio" name="licence" value="{{ $licence->ulid }}" class="mt-0.5 border-zinc-300 text-orange-600 focus:ring-orange-300" required>
                                        <span class="text-sm text-zinc-900">{{ $licence->holder->fullName() }} &middot; {{ $licence->licenceTypeLabel() }} &middot; {{ $licence->number }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <p class="text-sm text-zinc-600">{{ __('Once claimed, the code belongs to that licence and nobody else can claim it.') }}</p>
                            <x-button type="submit">{{ __('Claim the code') }}</x-button>
                        @endif
                    </form>
                @endauth
            @endif
        </div>
    </div>
</x-app-layout>
