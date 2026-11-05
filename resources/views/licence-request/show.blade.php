@use('App\Enums\LicenceRequestDocumentStatus')
@use('App\Enums\LicenceRequestPaymentStatus')
@use('App\Enums\LicenceRequestStatus')
@use('App\Models\PaymentChannelType')

@php
    $status = $licenceRequest->status;
    $isChangesRequested = $status === LicenceRequestStatus::ChangesRequested;
    $openChangeRequests = $isChangesRequested ? $licenceRequest->changeRequests->whereNull('resolved_at') : collect();
    $flaggedDocuments = $isChangesRequested ? $licenceRequest->documents->where('status', LicenceRequestDocumentStatus::Flagged) : collect();
    $isAwaitingPayment = $licenceRequest->payment_status === LicenceRequestPaymentStatus::AwaitingPayment && ! $status->isClosed();
    $requiredDocuments = $licenceRequest->allRequiredDocuments();
    $parents = $licenceRequest->parentHolders();

    $panelClasses = match ($status) {
        LicenceRequestStatus::ChangesRequested, LicenceRequestStatus::Rejected => 'border-red-300 bg-red-50 text-red-900',
        LicenceRequestStatus::Emitted => 'border-lime-300 bg-lime-50 text-lime-900',
        default => 'border-zinc-200 bg-white text-zinc-900',
    };

    [$panelTitle, $panelText] = match ($status) {
        LicenceRequestStatus::Draft => [__('Not submitted yet'), __('Fill in the remaining sections and submit the request. What you entered so far is saved.')],
        LicenceRequestStatus::Submitted, LicenceRequestStatus::InReview => [__('The organizer is checking your request'), __('You will get an email if something needs fixing.')],
        LicenceRequestStatus::ChangesRequested => [__('Some parts need fixing'), __('Fix what the organizer flagged, then send the request back.')],
        LicenceRequestStatus::Verified => [__('Your request is verified'), $licenceRequest->isPaymentSettled()
            ? __('The organizer is requesting your licence from :provider.', ['provider' => $licenceRequest->provider?->label()])
            : __('The organizer checked your request. Once the payment is confirmed, your licence is requested from :provider.', ['provider' => $licenceRequest->provider?->label()])],
        LicenceRequestStatus::Forwarded => [__('Sent to :provider', ['provider' => $licenceRequest->provider?->label()]), __('The provider emits the licence. It will appear in My licences, with the temporary licence to download.')],
        LicenceRequestStatus::Emitted => [__('Your licence is ready'), __('Download the temporary licence from the licence page.')],
        LicenceRequestStatus::Rejected => [__('Request rejected'), __('Write to the organizer for any question, including refunds.')],
    };

    $isPaymentSettled = $licenceRequest->isPaymentSettled();
    $steps = array_filter([
        [__('Submitted'), $licenceRequest->submitted_at !== null, $status === LicenceRequestStatus::Draft, $licenceRequest->submitted_at],
        [$isChangesRequested ? __('Organizer review: waiting for your fixes') : __('Organizer review'), $licenceRequest->verified_at !== null, in_array($status, [LicenceRequestStatus::Submitted, LicenceRequestStatus::InReview, LicenceRequestStatus::ChangesRequested], true), $licenceRequest->verified_at],
        [$licenceRequest->payment_status === LicenceRequestPaymentStatus::Covered ? __('Covered by your order') : __('Payment'), $isPaymentSettled, $status === LicenceRequestStatus::Verified && ! $isPaymentSettled, $licenceRequest->paid_at],
        $licenceRequest->forwarded_at !== null ? [__('Sent to :provider', ['provider' => $licenceRequest->provider?->label()]), true, false, $licenceRequest->forwarded_at] : null,
        [__('Licence emitted'), $licenceRequest->emitted_at !== null, $isPaymentSettled && in_array($status, LicenceRequestStatus::verified(), true), $licenceRequest->emitted_at],
    ]);
@endphp

<x-app-layout>
    <x-slot name="title">
        {{ __('Licence request') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex flex-wrap gap-2">
            <span><a href="{{ route('my-licences.index') }}">{{ __('My licences') }}</a></span>
            <span>/</span>
            <span>{{ $licenceRequest->holder ? __('Licence request for :holder', ['holder' => $licenceRequest->holder->fullName()]) : __('Licence request') }}</span>
        </h2>
        <p class="text-sm text-zinc-500">{{ $licenceRequest->licenceTypeLabel() ?? __('Licence not chosen yet') }} &middot; {{ __('For :year', ['year' => $licenceRequest->validity_year]) }} &middot; {{ $status->requesterLabel() }}</p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8 max-w-3xl space-y-6">

        <x-validation-errors />

        @include('partials.waiting-enrollments', ['licenceRequest' => $licenceRequest])

        <section class="rounded-md border p-4 space-y-3 {{ $panelClasses }}" role="status">
            <div>
                <h3 class="font-semibold">{{ $panelTitle }}</h3>
                <p class="text-sm">{{ $panelText }}</p>
            </div>

            @if ($status === LicenceRequestStatus::Rejected && $licenceRequest->rejection_reason)
                <p class="text-sm"><span class="font-semibold">{{ __('Reason') }}:</span> {{ $licenceRequest->rejection_reason }}</p>
            @endif

            @if ($openChangeRequests->isNotEmpty() || $flaggedDocuments->isNotEmpty())
                <ul class="list-disc pl-5 text-sm space-y-1">
                    @foreach ($openChangeRequests as $changeRequest)
                        <li><span class="font-semibold">{{ $changeRequest->section->label() }}:</span> {{ $changeRequest->note }}</li>
                    @endforeach
                    @foreach ($flaggedDocuments as $document)
                        <li><span class="font-semibold">{{ $document->document->label() }}:</span> {{ $document->note }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($isAwaitingPayment)
                <p class="text-sm font-medium">
                    {{ __('Pay :amount so the organizer can request your licence.', ['amount' => $licenceRequest->amountLabel()]) }}
                    <a href="#payment" class="text-orange-700 underline hover:text-orange-900">{{ __('How to pay') }}</a>
                </p>
            @endif

            @can('update', $licenceRequest)
                <x-button-link href="{{ route('licence-requests.edit', $licenceRequest) }}">
                    {{ $isChangesRequested ? __('Fix the flagged parts') : __('Continue the request') }}
                </x-button-link>
            @endcan

            @if ($licenceRequest->licence)
                <x-button-link href="{{ route('my-licences.show', $licenceRequest->licence) }}">{{ __('Open the licence') }}</x-button-link>
            @endif
        </section>

        @unless ($status === LicenceRequestStatus::Rejected)
            <section class="rounded-md border border-zinc-200 bg-white p-4">
                <h3 class="sr-only">{{ __('Progress') }}</h3>
                <ol class="space-y-3">
                    @foreach (array_values($steps) as $index => [$label, $isDone, $isCurrent, $date])
                        <li class="flex items-start gap-3 text-sm" @if ($isCurrent) aria-current="step" @endif>
                            <span @class([
                                'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-lime-600 text-white' => $isDone,
                                'bg-orange-600 text-white' => $isCurrent && ! $isDone,
                                'bg-zinc-100 text-zinc-500' => ! $isDone && ! $isCurrent,
                            ])>{{ $isDone ? '✓' : $index + 1 }}</span>
                            <span @class(['pt-0.5', 'font-semibold text-zinc-900' => $isCurrent, 'text-zinc-900' => $isDone && ! $isCurrent, 'text-zinc-500' => ! $isDone && ! $isCurrent])>
                                {{ $label }}
                                @if ($date)
                                    <span class="font-normal text-zinc-500">&middot; <x-time :value="$date" /></span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endunless

        <section id="payment" class="rounded-md border border-zinc-200 bg-white p-4 space-y-2 scroll-mt-4">
            <h3 class="font-semibold text-zinc-800">{{ __('Payment') }}</h3>
            <p class="text-sm">
                @if ($licenceRequest->amountLabel())
                    <span class="font-mono tabular-nums">{{ $licenceRequest->amountLabel() }}</span> &middot;
                @endif
                {{ $licenceRequest->payment_status->label() }}
            </p>
            @switch ($licenceRequest->payment_status)
                @case (LicenceRequestPaymentStatus::AwaitingPrice)
                    <p class="text-sm text-zinc-600">{{ __('The organizer sets the amount after checking your request. You can pay once it is set.') }}</p>
                    @break

                @case (LicenceRequestPaymentStatus::AwaitingPayment)
                    @can('pay', $licenceRequest)
                        <div class="space-y-2 text-sm">
                            <p>{{ __('Pay by bank transfer to:') }}</p>
                            <p class="rounded-sm border border-zinc-200 bg-zinc-50 p-2">
                                {{ config('races.organizer.bank_holder') }}
                                <br>{{ config('races.organizer.bank') }}
                                <br><span class="font-mono select-all break-all">{{ config('races.organizer.bank_account') }}</span>
                            </p>
                            <p>{{ __('Use the following text as the bank transfer reason:') }} <span class="font-mono select-all break-all">{{ $licenceRequest->ulid }}</span></p>
                            <p class="text-zinc-600">{{ __('You can also pay by cash or credit card at the organizer\'s office.') }}</p>

                            @if ($licenceRequest->latestPayment)
                                <p class="rounded-md border border-amber-400 bg-amber-50 px-2 py-1 text-amber-700">{{ __('The organizer could not confirm the previous payment proof. Upload a new one.') }}</p>
                            @endif

                            <form method="POST" action="{{ route('licence-requests.payment.store', $licenceRequest) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2" x-data="{ uploading: false }">
                                @csrf
                                <label class="inline-flex cursor-pointer items-center rounded-md border border-zinc-300 bg-white px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50 focus-within:ring-2 focus-within:ring-orange-300" :class="uploading && 'pointer-events-none opacity-60'">
                                    <input type="file" name="proof" accept="image/png,image/jpeg,application/pdf" class="sr-only" aria-label="{{ __('Payment proof') }}" required @change="uploading = true; $el.form.requestSubmit()">
                                    {{ $licenceRequest->latestPayment ? __('Choose a new payment proof') : __('Choose payment proof') }}
                                </label>
                                <span x-show="uploading" x-cloak class="text-zinc-600" role="status">{{ __('Uploading…') }}</span>
                            </form>
                            <p class="text-zinc-500">{{ __('PDF, JPG or PNG, up to 10 MB each.') }}</p>
                            <x-input-error for="proof" />
                        </div>
                    @endcan
                    @break

                @case (LicenceRequestPaymentStatus::PaymentSubmitted)
                    <p class="text-sm text-zinc-600">
                        {{ __('Payment proof uploaded. The organizer will confirm it.') }}
                        @if ($licenceRequest->latestPayment)
                            <span class="text-zinc-500">&middot; <x-time :value="$licenceRequest->latestPayment->created_at" /></span>
                        @endif
                    </p>
                    @break

                @case (LicenceRequestPaymentStatus::Paid)
                    <p class="text-sm text-lime-700">
                        @if (in_array($licenceRequest->payment_channel, [PaymentChannelType::CASH, PaymentChannelType::CREDIT_CARD], true))
                            {{ __('Paid at the organizer\'s office by :channel.', ['channel' => mb_strtolower($licenceRequest->payment_channel->localizedName())]) }}
                        @else
                            {{ __('Payment confirmed by the organizer.') }}
                        @endif
                    </p>
                    @break

                @case (LicenceRequestPaymentStatus::Covered)
                    <p class="text-sm text-zinc-600">{{ __('Covered by your order :order.', ['order' => $licenceRequest->coveringOrder?->ulid]) }}</p>
                    @break
            @endswitch
        </section>

        <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
            <h3 class="font-semibold text-zinc-800">{{ __('Your request') }}</h3>
            <dl class="grid grid-cols-3 gap-x-4 gap-y-1 text-sm">
                <dt class="text-zinc-500">{{ __('Licence') }}</dt>
                <dd class="col-span-2">{{ collect([$licenceRequest->provider?->label(), $licenceRequest->licenceTypeLabel()])->filter()->join(' · ') ?: '—' }}</dd>
                <dt class="text-zinc-500">{{ __('Last year\'s licence number') }}</dt>
                <dd class="col-span-2 font-mono break-all">{{ $licenceRequest->previous_licence_number ?? '—' }}</dd>
                <dt class="text-zinc-500">{{ __('Holder') }}</dt>
                <dd class="col-span-2">{{ $licenceRequest->holder?->fullName() ?? '—' }}</dd>
                @if ($parents !== [])
                    <dt class="text-zinc-500">{{ __('Parents or guardians') }}</dt>
                    <dd class="col-span-2">{{ collect($parents)->map->fullName()->join(', ') }}</dd>
                @endif
            </dl>

            @if ($licenceRequest->documents_pruned_at)
                <p class="border-t border-zinc-200 pt-2 text-sm text-zinc-600">{{ __('Documents deleted on :date after the retention period.', ['date' => $licenceRequest->documents_pruned_at->setTimezone(config('races.timezone'))->isoFormat('D MMMM YYYY')]) }}</p>
            @else
                <ul class="divide-y divide-zinc-200 border-t border-zinc-200 text-sm">
                    @foreach ($requiredDocuments as $required)
                        @php
                            $upload = $licenceRequest->uploadFor($required);
                            [$documentState, $documentClass] = match (true) {
                                $upload === null => [__('Not uploaded yet'), 'text-zinc-500'],
                                $isChangesRequested && $upload->status === LicenceRequestDocumentStatus::Flagged => [__('Needs changes'), 'text-red-700'],
                                $isChangesRequested && $upload->wasFlagged() => [__('Replaced'), 'text-lime-700'],
                                default => [__('Uploaded'), 'text-zinc-600'],
                            };
                        @endphp
                        <li class="flex flex-wrap justify-between gap-2 py-2">
                            <span>
                                {{ $required->document->label() }}
                                @if ($required->parentIndex !== null)
                                    <span class="text-zinc-500">&middot; {{ ($parents[$required->parentIndex] ?? null)?->fullName() }}</span>
                                @endif
                            </span>
                            <span class="{{ $documentClass }}">{{ $documentState }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @can('delete', $licenceRequest)
            <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                <h3 class="font-semibold text-zinc-800">{{ __('Delete draft') }}</h3>
                <p class="text-sm text-zinc-600">{{ __('Deleting the draft removes its files.') }}</p>

                <flux:modal.trigger name="confirm-request-deletion">
                    <x-danger-button>{{ __('Delete draft') }}</x-danger-button>
                </flux:modal.trigger>

                <flux:modal name="confirm-request-deletion" class="max-w-lg">
                    <form method="POST" action="{{ route('licence-requests.destroy', $licenceRequest) }}" class="space-y-6">
                        @method('DELETE')
                        @csrf

                        <div>
                            <flux:heading size="lg">{{ __('Delete this licence request?') }}</flux:heading>
                            <flux:subheading>{{ __('The draft and its uploaded files are removed and cannot be restored.') }}</flux:subheading>
                        </div>

                        <div class="flex justify-end gap-2">
                            <flux:modal.close>
                                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                            </flux:modal.close>

                            <flux:button variant="danger" type="submit">{{ __('Delete draft') }}</flux:button>
                        </div>
                    </form>
                </flux:modal>
            </section>
        @endcan
    </div>
</x-app-layout>
