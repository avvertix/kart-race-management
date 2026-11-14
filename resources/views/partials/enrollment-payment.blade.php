{{-- What to pay for an Enrollment and the payment proof upload. Expects $enrollment with payments loaded. --}}
@switch($enrollment->payment_status)
    @case(\App\Enums\EnrollmentPaymentStatus::AwaitingPayment)
    @case(\App\Enums\EnrollmentPaymentStatus::DepositPaid)
        @php($dueOn = $enrollment->balance_due_on?->isoFormat('D MMMM YYYY'))
        @if ($enrollment->deposit_amount !== null && ! $enrollment->isDepositPaid())
            <p>{{ __('Pay a deposit of :deposit now and the balance of :balance by :date, or :amount at once.', ['deposit' => $enrollment->currency->format($enrollment->deposit_amount - $enrollment->paidAmount()), 'balance' => $enrollment->currency->format($enrollment->amount - $enrollment->deposit_amount), 'date' => $dueOn, 'amount' => $enrollment->currency->format($enrollment->balance())]) }}</p>
        @elseif ($dueOn !== null)
            <p>{{ __('Pay the balance of :balance by :date.', ['balance' => $enrollment->currency->format($enrollment->balance()), 'date' => $dueOn]) }}</p>
        @elseif ($enrollment->paidAmount() > 0)
            <p>{{ __('Still to pay: :amount.', ['amount' => $enrollment->currency->format($enrollment->balance())]) }}</p>
        @endif

        <p>{{ __('Pay by bank transfer to:') }}</p>

        <p class="bg-white p-2 shadow-sm border border-zinc-200 rounded-sm">
            {{ config('races.organizer.bank_holder') }}
            <br>{{ config('races.organizer.bank') }}
            <br><span class="font-mono select-all">{{ config('races.organizer.bank_account') }}</span>
        </p>

        <p>{{ __('Use the following text as the bank transfer reason:') }}</p>
        <p class="not-prose bg-white shadow-sm border border-zinc-200 rounded-sm px-3 py-2 font-mono text-sm select-all">{{ $enrollment->bankTransferReason() }}</p>

        @if ($enrollment->payments->sortBy('id')->last()?->status === \App\Enums\PaymentStatus::Rejected)
            <p class="not-prose rounded-md text-sm text-amber-700 border border-amber-400 bg-amber-50 px-2 py-1">{{ __('The organizer could not confirm the previous payment proof. Upload a new one.') }}</p>
        @endif

        <p>{{ __('Once paid upload the bank transfer confirmation slip. Ensure that the sender\'s name, the transfer amount, the given reason, the date of the transfer and the bank account number are clearly visible.') }}</p>

        <form method="POST" enctype="multipart/form-data" action="{{ route('my-enrollments.payment.store', $enrollment) }}" class="not-prose">
            @csrf

            <x-input-error for="proof" class="mb-2" />

            <input type="file" class="block mb-1" name="proof" id="proof" accept="image/png,image/jpeg,application/pdf">
            <p class="text-sm mb-2">{{ __('Accept pdf file or png/jpg images (maximum size 10 MB)') }}</p>

            <x-button type="submit">
                {{ __('Submit payment receipt') }}
            </x-button>
        </form>
        @break

    @case(\App\Enums\EnrollmentPaymentStatus::PaymentSubmitted)
        <p>{{ __('Thanks for uploading the bank transfer confirmation slip. It may take up to five business days for the organizer to confirm the payment.') }}</p>
        @break

    @case(\App\Enums\EnrollmentPaymentStatus::Paid)
        <p>{{ __('Paid on :date.', ['date' => $enrollment->paid_at?->setTimezone(config('races.timezone'))->isoFormat('D MMMM YYYY')]) }}</p>
        @break
@endswitch
