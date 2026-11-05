<x-app-layout>
    <x-slot name="title">
        {{ __('Order :order', ['order' => $order->ulid]) }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ $order->lines->pluck('description')->join(', ') }}
        </h2>
        <p class="text-sm text-zinc-500">
            {{ __('Order :order', ['order' => $order->ulid]) }} &middot; {{ $order->status->label() }}
        </p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-4 prose prose-zinc">
                @if ($order->canStartLicenceRenewal())
                    <div class="not-prose rounded-md border border-lime-300 bg-lime-50 p-4 space-y-2">
                        <p class="text-sm text-lime-900">{{ __('Your order includes a licence renewal. Start it now: the request is covered by this order and asks for no payment.') }}</p>
                        <form method="POST" action="{{ route('licence-requests.store') }}">
                            @csrf
                            <input type="hidden" name="order" value="{{ $order->ulid }}">
                            <x-button type="submit">{{ __('Start your licence renewal') }}</x-button>
                        </form>
                        <x-input-error for="order" class="mt-2" />
                    </div>
                @endif
                @include('partials.order-payment', ['order' => $order, 'paymentAction' => route('my-orders.payment.store', $order)])
            </div>

            <aside class="rounded-md border border-zinc-200 bg-white p-4 space-y-2 self-start">
                @foreach ($order->lines as $line)
                    <div class="flex justify-between gap-4">
                        <span>{{ $line->description }}</span>
                        <span class="font-mono tabular-nums">{{ $line->amount === null ? $line->price->label() : $order->currency->format($line->amount) }}</span>
                    </div>
                @endforeach

                <div class="flex justify-between gap-4 border-t border-zinc-200 pt-2 font-semibold">
                    <span>{{ __('Total') }}</span>
                    <span class="font-mono tabular-nums">{{ $order->total === null ? __('to be set') : $order->currency->format($order->total) }}</span>
                </div>

                @if ($order->price_override_reason)
                    <p class="text-sm text-zinc-600">{{ $order->price_override_reason }}</p>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
