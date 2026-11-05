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
                @switch($order->status)
                    @case(\App\Enums\OrderStatus::AwaitingPrice)
                        <p>{{ __('The organizer sets the final price after reviewing your request. You will receive an email once it is set, then you can pay.') }}</p>
                        @break

                    @case(\App\Enums\OrderStatus::AwaitingPayment)
                        <p>{{ __('Pay by bank transfer to:') }}</p>

                        <p class="bg-white p-2 shadow-sm border border-zinc-200 rounded-sm">
                            {{ config('races.organizer.bank_holder') }}
                            <br>{{ config('races.organizer.bank') }}
                            <br><span class="font-mono select-all">{{ config('races.organizer.bank_account') }}</span>
                        </p>

                        <p>{{ __('Use the following text as the bank transfer reason:') }}</p>
                        <p class="not-prose bg-white shadow-sm border border-zinc-200 rounded-sm px-3 py-2 font-mono text-sm select-all">{{ $order->ulid }}</p>

                        @if ($order->latestPayment)
                            <p class="not-prose rounded-md text-sm text-amber-700 border border-amber-400 bg-amber-50 px-2 py-1">{{ __('The organizer could not confirm the previous payment proof. Upload a new one.') }}</p>
                        @endif

                        <p>{{ __('Once paid upload the bank transfer confirmation slip. Ensure that the sender\'s name, the transfer amount, the given reason, the date of the transfer and the bank account number are clearly visible.') }}</p>

                        <form method="POST" enctype="multipart/form-data" action="{{ route('my-orders.payment.store', $order) }}" class="not-prose">
                            @csrf

                            <x-input-error for="proof" class="mb-2" />

                            <input type="file" class="block mb-1" name="proof" id="proof" accept="image/png,image/jpeg,application/pdf">
                            <p class="text-sm mb-2">{{ __('Accept pdf file or png/jpg images (maximum size 10 MB)') }}</p>

                            <x-button type="submit">
                                {{ __('Submit payment receipt') }}
                            </x-button>
                        </form>
                        @break

                    @case(\App\Enums\OrderStatus::PaymentSubmitted)
                        <p>{{ __('Thanks for uploading the bank transfer confirmation slip. It may take up to five business days for the organizer to confirm the payment.') }}</p>
                        @break

                    @case(\App\Enums\OrderStatus::Paid)
                        <p>{{ __('Paid on :date.', ['date' => $order->paid_at?->setTimezone(config('races.timezone'))->isoFormat('D MMMM YYYY')]) }}</p>
                        @break

                    @case(\App\Enums\OrderStatus::Cancelled)
                        <p>{{ __('The order was cancelled.') }}</p>
                        @break
                @endswitch
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
