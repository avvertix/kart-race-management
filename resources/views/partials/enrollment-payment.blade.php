{{-- What to pay for an Enrollment and the payment proof upload. Expects $enrollment with payments loaded. --}}
@php
    $valueBox = 'flex flex-wrap items-center justify-between gap-2 rounded-md border border-zinc-200 bg-zinc-50 px-3 py-1';
    $copyButton = 'min-h-11 shrink-0 rounded-md px-3 text-sm font-medium text-orange-600 hover:text-orange-900 focus:outline-none focus-visible:ring-3 focus-visible:ring-orange-300';
@endphp

<div class="space-y-4 text-sm text-zinc-700">
    @switch($enrollment->payment_status)
        @case(\App\Enums\EnrollmentPaymentStatus::AwaitingPayment)
        @case(\App\Enums\EnrollmentPaymentStatus::DepositPaid)
            @php
                $dueOn = $enrollment->balance_due_on?->isoFormat('D MMMM YYYY');
                $format = fn (int $amount) => $enrollment->currency->format($amount);
                $rows = match (true) {
                    $enrollment->deposit_amount !== null && ! $enrollment->isDepositPaid() => [
                        [__('Deposit, now'), $format($enrollment->deposit_amount - $enrollment->paidAmount()), true],
                        [$dueOn !== null ? __('Balance, by :date', ['date' => $dueOn]) : __('Balance'), $format($enrollment->amount - $enrollment->deposit_amount), false],
                        [__('Or all at once'), $format($enrollment->balance()), false],
                    ],
                    $dueOn !== null => [[__('Balance, by :date', ['date' => $dueOn]), $format($enrollment->balance()), true]],
                    $enrollment->paidAmount() > 0 => [[__('Still to pay'), $format($enrollment->balance()), true]],
                    default => [],
                };
            @endphp

            @if ($rows !== [])
                <dl class="max-w-sm divide-y divide-zinc-200 rounded-md border border-zinc-200 bg-white">
                    @foreach ($rows as [$label, $amount, $isNext])
                        <div class="flex items-baseline justify-between gap-4 px-3 py-2">
                            <dt @class(['font-medium text-zinc-900' => $isNext])>{{ $label }}</dt>
                            <dd @class(['tabular-nums', 'font-medium text-zinc-900' => $isNext])>{{ $amount }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            <section class="max-w-prose space-y-2">
                <h4 class="font-medium text-zinc-900">{{ __('Pay by bank transfer to:') }}</h4>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-xs text-zinc-500">{{ __('Account holder') }}</dt>
                        <dd class="text-zinc-900">{{ config('races.organizer.bank_holder') }}</dd>
                    </div>
                    @if (filled(config('races.organizer.bank')))
                        <div>
                            <dt class="text-xs text-zinc-500">{{ __('Bank') }}</dt>
                            <dd class="text-zinc-900">{{ config('races.organizer.bank') }}</dd>
                        </div>
                    @endif
                    @foreach ([
                        ['label' => __('IBAN'), 'value' => config('races.organizer.bank_account')],
                        ['label' => __('Bank transfer reason'), 'value' => $enrollment->bankTransferReason()],
                    ] as $copyable)
                        <div x-data="{ copied: false, copy() { navigator.clipboard?.writeText(this.$refs.value.textContent.trim()).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }">
                            <dt class="text-xs text-zinc-500">{{ $copyable['label'] }}</dt>
                            <dd class="{{ $valueBox }}">
                                <span x-ref="value" class="font-mono text-zinc-900 select-all break-all">{{ $copyable['value'] }}</span>
                                <button type="button" @click="copy()" class="{{ $copyButton }}" aria-label="{{ __('Copy :what', ['what' => $copyable['label']]) }}">
                                    <span x-show="!copied">{{ __('Copy') }}</span>
                                    <span x-show="copied" x-cloak role="status">{{ __('Copied') }}</span>
                                </button>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @include('partials.payment-proof-upload', [
                'action' => route('my-enrollments.payment.store', $enrollment),
                'replacesRejected' => $enrollment->payments->sortBy('id')->last()?->status === \App\Enums\PaymentStatus::Rejected,
                'instructions' => __('Then upload the transfer receipt: the sender, amount, reason, date and IBAN must be readable.'),
            ])
            @break

        @case(\App\Enums\EnrollmentPaymentStatus::PaymentSubmitted)
            @php($submitted = $enrollment->payments->sortBy('id')->last(fn ($payment) => $payment->status === \App\Enums\PaymentStatus::Submitted && $payment->hasProof()))
            <div class="max-w-prose rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 space-y-1">
                <p class="font-medium text-zinc-900">{{ __('Payment proof uploaded') }}</p>
                @if ($submitted)
                    <p class="text-zinc-600">
                        <x-time :value="$submitted->created_at" /> &middot; {{ $submitted->status->label() }}
                        &middot; <a href="{{ $submitted->downloadUrl }}" target="_blank" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}</a>
                    </p>
                @endif
                <p class="text-zinc-600">{{ __('It may take up to five business days for the organizer to confirm the payment.') }}</p>
            </div>
            @break

        @case(\App\Enums\EnrollmentPaymentStatus::Paid)
            <p>{{ __('Paid on :date.', ['date' => $enrollment->paid_at?->setTimezone(config('races.timezone'))->isoFormat('D MMMM YYYY')]) }}</p>
            @break
    @endswitch
</div>
