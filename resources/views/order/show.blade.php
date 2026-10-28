@php
    $line = $order->lines->first();
    $lockedPrice = $line?->price;
    $suggestedAmount = $order->total ?? $lockedPrice?->amount_min ?? $lockedPrice?->amount;
@endphp

<x-app-layout>
    <x-slot name="title">
        {{ __('Order :order', ['order' => $order->ulid]) }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('orders.index') }}">{{ __('Orders') }}</a></span>
            <span>/</span>
            <span>{{ $order->user?->name }} &middot; {{ $order->lines->pluck('description')->join(', ') }}</span>
        </h2>
        <p class="text-sm text-zinc-500">
            {{ $order->ulid }} &middot; {{ $order->status->label() }} &middot; <x-time :value="$order->created_at" />
        </p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">

            <div class="lg:col-span-2 space-y-6">
                <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
                    <h3 class="font-semibold text-zinc-800">{{ __('Payment proof') }}</h3>

                    @if ($order->latestPayment)
                        <p class="text-sm text-zinc-600">
                            {{ __('Uploaded on') }} <x-time :value="$order->latestPayment->created_at" />
                            &middot; <a href="{{ $order->latestPayment->downloadUrl }}" target="_blank" class="text-orange-600 hover:text-orange-900">{{ __('Open in a new tab') }}</a>
                        </p>
                        <iframe src="{{ $order->latestPayment->downloadUrl }}" class="w-full h-96 rounded-sm border border-zinc-200" title="{{ __('Payment proof') }}"></iframe>
                    @else
                        <p class="text-sm text-zinc-600">{{ __('No payment proof uploaded yet.') }}</p>
                    @endif

                    @if ($order->status === \App\Enums\OrderStatus::PaymentSubmitted)
                        @can('update', $order)
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('orders.confirm-payment', $order) }}">
                                    @csrf
                                    <x-button type="submit">{{ __('Confirm payment') }}</x-button>
                                </form>
                                <form method="POST" action="{{ route('orders.reject-payment', $order) }}">
                                    @csrf
                                    <x-secondary-button type="submit">{{ __('Reject payment') }}</x-secondary-button>
                                </form>
                            </div>
                            <x-input-error for="order" />
                        @endcan
                    @endif
                </section>
            </div>

            <aside class="space-y-4">
                <div class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                    @foreach ($order->lines as $item)
                        <div class="flex justify-between gap-4">
                            <span>{{ $item->description }}</span>
                            <span class="font-mono tabular-nums">{{ $item->amount === null ? '—' : $order->currency->format($item->amount) }}</span>
                        </div>
                        <p class="text-sm text-zinc-500">{{ __('Locked price: :price', ['price' => $item->price->label()]) }}</p>
                    @endforeach

                    <div class="flex justify-between gap-4 border-t border-zinc-200 pt-2 font-semibold">
                        <span>{{ __('Total') }}</span>
                        <span class="font-mono tabular-nums">{{ $order->total === null ? '—' : $order->currency->format($order->total) }}</span>
                    </div>

                    @if ($order->price_override_reason)
                        <p class="text-sm text-zinc-600">{{ $order->price_override_reason }}</p>
                    @endif
                </div>

                @if ($order->canBePriced())
                    @can('update', $order)
                        <form method="POST" action="{{ route('orders.price', $order) }}" class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
                            @method('PUT')
                            @csrf

                            <h3 class="font-semibold text-zinc-800">{{ __('Set the price') }}</h3>
                            <p class="text-sm text-zinc-600">{{ __('The price can change until a payment proof is uploaded. An amount outside :price needs a reason, shown to the user.', ['price' => $lockedPrice?->label()]) }}</p>

                            <div>
                                <x-label for="amount" value="{{ __('Amount (€)') }}*" />
                                <x-input id="amount" type="text" inputmode="decimal" name="amount" :value="old('amount', $suggestedAmount === null ? null : number_format($suggestedAmount / 100, 2, '.', ''))" class="mt-1 block w-full" required />
                                <x-input-error for="amount" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="price_override_reason" value="{{ __('Reason') }}" />
                                <x-textarea id="price_override_reason" name="price_override_reason" class="mt-1 block w-full">{{ old('price_override_reason', $order->price_override_reason) }}</x-textarea>
                                <x-input-error for="price_override_reason" class="mt-2" />
                            </div>

                            <x-button type="submit">{{ __('Set price') }}</x-button>
                        </form>
                    @endcan
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
