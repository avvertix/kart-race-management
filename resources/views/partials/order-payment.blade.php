{{-- What to pay for an Order and the payment proof upload. Expects $order and $paymentAction, the URL the proof is posted to. --}}
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

        <form method="POST" enctype="multipart/form-data" action="{{ $paymentAction }}" class="not-prose">
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
