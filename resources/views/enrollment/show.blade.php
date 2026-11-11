@php
    $championship = $enrollment->championship;
    $licence = $enrollment->licence;
@endphp

<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ $enrollment->holder()?->fullName() }} - {{ __('Enrollments') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        <x-button-link href="{{ route('championships.enrollments.index', [$championship, 'status' => $enrollment->status->value]) }}">
            {{ __('All enrollments') }}
        </x-button-link>
    </x-slot>

    <x-validation-errors class="mb-4" />

    <div class="grid gap-6 md:grid-cols-2">
        <div class="p-4 bg-white shadow-sm rounded-sm space-y-3">
            <p class="text-2xl font-bold">{{ $enrollment->holder()?->fullName() }}</p>
            <p>
                <span class="block text-zinc-500">{{ __('Status') }}</span>
                {{ $enrollment->status->label() }}
                @if ($enrollment->confirmed_at)
                    &middot; <x-time :value="$enrollment->confirmed_at" />
                @endif
            </p>
            @if ($enrollment->rejection_reason)
                <p>
                    <span class="block text-zinc-500">{{ __('Rejection reason') }}</span>
                    {{ $enrollment->rejection_reason }}
                </p>
            @endif
            <p>
                <span class="block text-zinc-500">{{ __('Licence') }}</span>
                @if ($licence)
                    {{ $licence->provider->label() }} &middot; {{ $licence->licenceTypeLabel() }} &middot; <span class="font-mono">{{ $licence->number }}</span> &middot; {{ $licence->validity_year }}
                    <span class="block text-sm text-zinc-600">{{ $licence->source->label() }}{{ $licence->isVerifiedOrEmitted() ? ' · '.__('Verified') : '' }}</span>
                @elseif ($enrollment->licenceRequest)
                    @if ($enrollment->licenceRequest->status !== \App\Enums\LicenceRequestStatus::Draft && auth()->user()->can('review', $enrollment->licenceRequest))
                        <a href="{{ route('licence-request-reviews.show', $enrollment->licenceRequest) }}" class="text-orange-600 hover:text-orange-900">{{ __('Waiting for the licence request') }}</a>
                    @else
                        {{ __('Waiting for the licence request') }}
                    @endif
                    &middot; {{ $enrollment->licenceRequest->status->label() }}
                @endif
            </p>
            <p>
                <span class="block text-zinc-500">{{ __('Competitor') }}</span>
                {{ $enrollment->competitorLicence ? $enrollment->competitorLicence->holder->fullName().' · '.$enrollment->competitorLicence->number : __('No competitor') }}
                @can('changeCompetitor', $enrollment)
                    @if ($competitorLicences->isNotEmpty())
                        <form method="POST" action="{{ route('enrollments.competitor.update', $enrollment) }}" class="mt-2 flex flex-wrap items-end gap-2">
                            @csrf
                            @method('PUT')
                            <select name="competitor_licence" aria-label="{{ __('Competitor') }}" class="rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                <option value="">{{ __('No competitor') }}</option>
                                @foreach ($competitorLicences as $competitor)
                                    <option value="{{ $competitor->ulid }}" @selected($enrollment->competitor_licence_id === $competitor->getKey())>{{ $competitor->holder->fullName() }} &middot; {{ $competitor->number }}</option>
                                @endforeach
                            </select>
                            <x-secondary-button type="submit">{{ __('Save competitor') }}</x-secondary-button>
                        </form>
                        <x-input-error for="competitor_licence" class="mt-1" />
                    @endif
                @endcan
                @if ($enrollment->isDriverUnderage())
                    <span class="block text-sm text-zinc-600">{{ __('The driver is underage.') }}</span>
                @endif
            </p>
            <div>
                <span class="block text-zinc-500">{{ __('Payment') }}</span>
                <p>
                    {{ $enrollment->option?->title ?? __('Enrolled by the organizer') }}
                    &middot; <span class="font-mono tabular-nums">{{ $enrollment->amount === 0 ? __('Free') : $enrollment->currency->format($enrollment->amount) }}</span>
                    &middot; {{ $enrollment->payment_status->label() }}
                    @php($paymentTerms = $enrollment->paymentTermsLabel())
                    @if ($paymentTerms !== null)
                        <span class="block text-sm text-zinc-600">{{ $paymentTerms }}</span>
                    @endif
                </p>
                <div class="mt-2">
                    @include('partials.enrollment-payment-records', ['enrollment' => $enrollment])
                </div>
                @php($proof = $enrollment->payments->firstWhere('status', \App\Enums\PaymentStatus::Submitted))
                @if ($proof !== null && $enrollment->status->isActive())
                    <iframe src="{{ $proof->downloadUrl }}" class="mt-2 w-full h-96 rounded-sm border border-zinc-200" title="{{ __('Payment proof') }}"></iframe>
                    <div class="mt-2 flex flex-wrap items-end gap-2">
                        <form method="POST" action="{{ route('enrollments.payment.confirm', $enrollment) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <x-label for="confirm_amount" :value="__('Amount received (€)')" />
                                <x-input id="confirm_amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-32" :value="old('amount', number_format($enrollment->balance() / 100, 2, '.', ''))" required />
                            </div>
                            <x-button type="submit">{{ __('Confirm payment') }}</x-button>
                        </form>
                        <form method="POST" action="{{ route('enrollments.payment.reject', $enrollment) }}">
                            @csrf
                            <x-secondary-button type="submit">{{ __('Reject payment') }}</x-secondary-button>
                        </form>
                    </div>
                    <p class="mt-1 text-sm text-zinc-600">{{ __('Type the amount you see on the bank statement.') }}</p>
                @elseif (in_array($enrollment->payment_status, [\App\Enums\EnrollmentPaymentStatus::AwaitingPayment, \App\Enums\EnrollmentPaymentStatus::DepositPaid], true) && ($enrollment->status->isActive() || $enrollment->canBeBroughtBack()))
                    @if ($enrollment->status === \App\Enums\EnrollmentStatus::Withdrawn)
                        <p class="mt-2 text-sm text-zinc-600">{{ __('Recording a payment brings the withdrawn enrollment back, until the first race starts.') }}</p>
                    @endif
                    <form method="POST" action="{{ route('enrollments.payment.record', $enrollment) }}" class="mt-2 flex flex-wrap items-end gap-2">
                        @csrf
                        <div>
                            <x-label for="record_channel" :value="__('Received by')" />
                            <select id="record_channel" name="channel" class="mt-1 rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                                @foreach (\App\Models\PaymentChannelType::cases() as $channel)
                                    <option value="{{ $channel->value }}" @selected((int) old('channel') === $channel->value)>{{ $channel->localizedName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-label for="record_amount" :value="__('Amount received (€)')" />
                            <x-input id="record_amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-32" :value="old('amount', number_format($enrollment->balance() / 100, 2, '.', ''))" required />
                        </div>
                        <x-secondary-button type="submit">{{ __('Record payment') }}</x-secondary-button>
                    </form>
                @endif
                <x-input-error for="amount" class="mt-1" />
                <x-input-error for="channel" class="mt-1" />
                <x-input-error for="payment" class="mt-1" />
                @if ($enrollment->canBeBroughtBack())
                    <form method="POST" action="{{ route('enrollments.bring-back', $enrollment) }}" class="mt-2 space-y-1">
                        @csrf
                        <p class="text-sm text-zinc-600">{{ __('Until the first race starts you can bring the withdrawn enrollment back, with what it paid.') }}</p>
                        <x-secondary-button type="submit">{{ __('Bring back') }}</x-secondary-button>
                    </form>
                @endif
                @if ($enrollment->canBeCredited() && $enrollment->championship->entitlements_enabled)
                    @php($coveredRenewal = $enrollment->coveredEmittedRenewal())
                    <form method="POST" action="{{ route('enrollments.credit', $enrollment) }}" class="mt-2 space-y-2">
                        @csrf
                        <p class="text-sm text-zinc-600">{{ __('Turn the :amount paid into a race fee promo code to hand to the driver. Race fee discounts of the option are cancelled where unused, and those already used are taken off. The enrollment can\'t come back afterwards.', ['amount' => $enrollment->currency->format($enrollment->paidAmount())]) }}</p>
                        @if ($coveredRenewal !== null)
                            @php($renewalPrice = \App\Support\LicenceCatalogue::lowestRenewalPrice())
                            <div>
                                <x-label for="renewal_deduction" :value="__('Licence renewal to take off (€)')" />
                                <x-input id="renewal_deduction" name="renewal_deduction" type="text" inputmode="decimal" class="mt-1 block w-32" :value="old('renewal_deduction', $renewalPrice === null ? '' : number_format($renewalPrice / 100, 2, '.', ''))" required />
                                <p class="mt-1 text-sm text-zinc-600">{{ __('The enrollment paid the licence renewal, which was emitted.') }}</p>
                                <x-input-error for="renewal_deduction" class="mt-1" />
                            </div>
                        @endif
                        <x-secondary-button type="submit">{{ __('Turn into a promo code') }}</x-secondary-button>
                    </form>
                    <x-input-error for="credit" class="mt-1" />
                @endif
                @if ($enrollment->creditPromoCode)
                    <p class="mt-2 text-sm text-zinc-700">{{ __('Promo code :code for :amount, to hand to the driver.', ['code' => $enrollment->creditPromoCode->code, 'amount' => $enrollment->creditPromoCode->mode->describe($enrollment->creditPromoCode->amount)]) }}</p>
                @endif
                @if ($enrollment->status === \App\Enums\EnrollmentStatus::Submitted && ! in_array($enrollment->payment_status, [\App\Enums\EnrollmentPaymentStatus::Paid, \App\Enums\EnrollmentPaymentStatus::PaymentSubmitted], true) && $options->count() > 1)
                    <form method="POST" action="{{ route('enrollments.option.update', $enrollment) }}" class="mt-2 flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PUT')
                        <select name="option" aria-label="{{ __('Enrollment option') }}" class="rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50">
                            @foreach ($options as $item)
                                <option value="{{ $item->ulid }}" @selected($enrollment->enrollment_option_id === $item->getKey())>{{ $item->title }} &middot; {{ $item->amountLabel() }}</option>
                            @endforeach
                        </select>
                        <x-secondary-button type="submit">{{ __('Change option') }}</x-secondary-button>
                    </form>
                    <x-input-error for="option" class="mt-1" />
                @endif
            </div>
            @if ($enrollment->reservations->isNotEmpty())
                <p>
                    <span class="block text-zinc-500">{{ __('Race number') }}</span>
                    <span class="block text-sm text-zinc-600">{{ __('Confirm it on its own at any time, also before the payment; confirming the enrollment confirms it too.') }}</span>
                    @foreach ($enrollment->reservations as $reservation)
                        <span class="font-mono">{{ $reservation->bib }}</span> &middot; {{ $reservation->status->label() }}
                        @can('confirm', $reservation)
                            <form method="POST" action="{{ route('bib-reservations.confirm', $reservation) }}" class="inline">
                                @csrf
                                <input type="hidden" name="return" value="enrollment">
                                <button type="submit" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Confirm number :bib', ['bib' => $reservation->bib]) }}</button>
                            </form>
                        @endcan
                    @endforeach
                </p>
            @endif
            <div>
                <span class="block text-zinc-500">{{ __('Previous numbers') }}</span>
                @forelse ($previousNumbers as $item)
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-100 py-2 first:border-t-0">
                        <p class="text-sm">
                            <span class="font-mono font-semibold">{{ $item['bib'] }}</span>
                            &middot; {{ $item['championship'] }}
                            &middot; {{ trans_choice(':count race|:count races', $item['races'], ['count' => $item['races']]) }}
                            <span class="block text-zinc-600">{{ $item['driver'] }} &middot; {{ collect($item['matches'])->map->label()->join(', ') }}</span>
                        </p>
                        @if ($canPickNumber && $availability->isFree($item['bib']))
                            <form method="POST" action="{{ route('enrollments.race-number.update', $enrollment) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="bib" value="{{ $item['bib'] }}">
                                <x-secondary-button type="submit">{{ __('Use number :bib', ['bib' => $item['bib']]) }}</x-secondary-button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-zinc-600">{{ __('No numbers found in past championships.') }}</p>
                @endforelse
            </div>
            <p>
                <span class="block text-zinc-500">{{ __('Submitted by') }}</span>
                {{ $enrollment->user->name }} &middot; <x-time :value="$enrollment->created_at" />
            </p>
        </div>

        <div class="space-y-6">
            @if ($enrollment->status === \App\Enums\EnrollmentStatus::Submitted)
                @if ($missing !== [])
                    <div class="rounded-md border border-yellow-300 bg-yellow-50 p-4 space-y-1" role="status">
                        <p class="font-semibold text-yellow-900">{{ __('Not ready to confirm') }}</p>
                        <ul class="list-disc pl-5 text-sm text-yellow-900">
                            @foreach ($missing as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    @if ($licence && ! $licence->isVerifiedOrEmitted())
                        @can('verify', $licence)
                            <form method="POST" action="{{ route('enrollments.verify-licence', $enrollment) }}">
                                @csrf
                                <x-secondary-button type="submit">{{ __('Mark licence as verified') }}</x-secondary-button>
                            </form>
                        @endcan
                    @endif

                    @if ($missing === [])
                        <form method="POST" action="{{ route('enrollments.confirm', $enrollment) }}">
                            @csrf
                            <x-button type="submit">{{ __('Confirm enrollment') }}</x-button>
                        </form>
                    @endif
                </div>

                @if ($canPickNumber)
                    <form method="POST" action="{{ route('enrollments.race-number.update', $enrollment) }}" class="space-y-3 rounded-md border border-zinc-200 bg-white p-4">
                        @csrf
                        @method('PUT')
                        <livewire:race-number-picker :championship="$championship" :enrollment="$enrollment" :show-holders="true" />
                        <x-secondary-button type="submit">{{ __('Save race number') }}</x-secondary-button>
                    </form>
                @endif

                @if ($enrollment->hasPaymentUnderReview())
                    <p class="text-sm text-zinc-600">{{ __('A payment proof waits for review: confirm or reject it before rejecting the enrollment.') }}</p>
                @else
                <form method="POST" action="{{ route('enrollments.reject', $enrollment) }}" class="space-y-2">
                    @csrf
                    <x-label for="reason" value="{{ __('Rejection reason') }}" />
                    <x-textarea id="reason" name="reason" rows="3" class="block w-full" required>{{ old('reason') }}</x-textarea>
                    <x-input-error for="reason" class="mt-2" />
                    <x-danger-button type="submit">{{ __('Reject enrollment') }}</x-danger-button>
                </form>
                @endif
            @endif

            @if ($enrollment->status === \App\Enums\EnrollmentStatus::Submitted && $enrollment->hasPaymentUnderReview())
                <p class="text-sm text-zinc-600">{{ __('A payment proof waits for review: confirm or reject it before withdrawing.') }}</p>
            @elseif ($enrollment->status === \App\Enums\EnrollmentStatus::Submitted)
                <form method="POST" action="{{ route('enrollments.withdraw', $enrollment) }}" class="space-y-2">
                    @csrf
                    <p class="text-sm text-zinc-600">{{ __('Withdrawing releases the race number and returns a covered licence request to its own payment. Until the first race you can bring it back, or turn what was paid into a promo code. Refunds are settled with the driver.') }}</p>
                    <x-danger-button type="submit">{{ __('Withdraw enrollment') }}</x-danger-button>
                </form>
            @endif
        </div>
    </div>
</x-championship-page-layout>
