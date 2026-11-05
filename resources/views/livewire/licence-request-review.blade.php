@use('App\Enums\LicenceRequestDocumentStatus')
@use('App\Enums\LicenceRequestPaymentStatus')
@use('App\Enums\LicenceRequestSection')
@use('App\Enums\LicenceRequestStatus')
@use('App\Models\PaymentChannelType')
@use('App\Support\LicenceCatalogue')

@php
    $status = $licenceRequest->status;
    $isDraft = $status === LicenceRequestStatus::Draft;
    $isReviewable = in_array($status, LicenceRequestStatus::underReview(), true);
    $isVerified = in_array($status, LicenceRequestStatus::verified(), true);
    $paymentStatus = $licenceRequest->payment_status;
    $isPaymentSettled = $licenceRequest->isPaymentSettled();
    $providerLabel = $licenceRequest->provider?->label();
    $parents = $licenceRequest->parentHolders();
    $changesRequestedAt = $licenceRequest->changes_requested_at;

    $openFlags = $licenceRequest->changeRequests->whereNull('resolved_at');
    $documentComments = $licenceRequest->documents->where('status', LicenceRequestDocumentStatus::Flagged);
    $commentCount = $openFlags->count() + $documentComments->count();
    $viewedCount = $requiredDocuments->filter(fn ($required) => $licenceRequest->uploadFor($required)?->status === LicenceRequestDocumentStatus::Accepted)->count();
    $documentCount = $requiredDocuments->count();
    $canVerify = $commentCount === 0 && $licenceRequest->isEveryDocumentViewed();

    $steps = [
        [
            __('Review'),
            $licenceRequest->verified_at !== null,
            $isReviewable || $status === LicenceRequestStatus::ChangesRequested,
            match (true) {
                $licenceRequest->verified_at !== null => LicenceRequestStatus::Verified->label(),
                $status === LicenceRequestStatus::ChangesRequested => __('Waiting for the driver\'s fixes'),
                default => __(':viewed of :total documents viewed', ['viewed' => $viewedCount, 'total' => $documentCount])
                    .' · '.trans_choice(':count comment|:count comments', $commentCount),
            },
        ],
        [
            __('Payment'),
            $isPaymentSettled,
            $isVerified && ! $isPaymentSettled,
            match ($paymentStatus) {
                LicenceRequestPaymentStatus::AwaitingPrice => __('You set the amount when you finish the review'),
                LicenceRequestPaymentStatus::AwaitingPayment => __('Waiting for the driver\'s payment of :amount', ['amount' => $licenceRequest->amountLabel()]),
                LicenceRequestPaymentStatus::PaymentSubmitted => __('Payment proof to confirm, :amount', ['amount' => $licenceRequest->amountLabel()]),
                LicenceRequestPaymentStatus::Paid => $licenceRequest->payment_channel
                    ? __('Paid by :channel, :amount', ['channel' => mb_strtolower($licenceRequest->payment_channel->localizedName()), 'amount' => $licenceRequest->amountLabel()])
                    : __('Paid, :amount', ['amount' => $licenceRequest->amountLabel()]),
                LicenceRequestPaymentStatus::Covered => __('Covered by a package'),
            },
        ],
        [
            __('Licence'),
            $status === LicenceRequestStatus::Emitted,
            $isVerified && $isPaymentSettled,
            match (true) {
                $status === LicenceRequestStatus::Emitted => LicenceRequestStatus::Emitted->label(),
                $status === LicenceRequestStatus::Forwarded => __('With :provider, waiting for the licence', ['provider' => $providerLabel]),
                default => __('Emit it once the payment is settled'),
            },
        ],
    ];

    $badgeClasses = [
        'todo' => 'bg-zinc-100 text-zinc-700',
        'viewed' => 'bg-lime-100 text-lime-800',
        'comment' => 'bg-red-100 text-red-800',
        'new' => 'bg-sky-100 text-sky-800',
        'missing' => 'bg-zinc-100 text-zinc-500',
    ];
    $cardClass = 'rounded-md border border-zinc-200 bg-white p-4 space-y-3';
    $commentButtonClass = 'text-sm font-medium text-orange-600 hover:text-orange-900';
@endphp

<x-slot name="title">
    {{ $licenceRequest->holder?->fullName() ?? __('New licence request') }}
</x-slot>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex flex-wrap gap-2">
        <span><a href="{{ route('licence-request-reviews.index') }}">{{ __('Licence requests') }}</a></span>
        <span>/</span>
        <span>{{ $licenceRequest->holder?->fullName() ?? __('New licence request') }}</span>
    </h2>
    <p class="text-sm text-zinc-500 flex flex-wrap items-center gap-x-2">
        <span class="inline-block px-2 rounded-full bg-zinc-100 text-xs font-medium text-zinc-700">{{ $status->label() }}</span>
        <span>{{ $licenceRequest->licenceTypeLabel() ?? __('Licence not chosen yet') }}</span>
        <span>&middot; {{ __('For :year', ['year' => $licenceRequest->validity_year]) }}</span>
        <span>&middot; {{ __('Account: :user', ['user' => $licenceRequest->user->name]) }}</span>
        @if ($licenceRequest->submitted_at)
            <span>&middot; {{ __('Submitted') }} <x-time :value="$licenceRequest->submitted_at" /></span>
        @endif
    </p>
</x-slot>

<div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8 space-y-4">
    <x-validation-errors />

    @if ($isDraft)
        <section class="rounded-md border border-zinc-200 bg-white p-4" role="status">
            <h3 class="font-semibold text-zinc-900">{{ __('Not submitted yet') }}</h3>
            <p class="text-sm text-zinc-600">{{ __('The driver is still filling it in. Below is what is filled so far.') }}</p>
        </section>
    @else
        <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-4" aria-labelledby="next-step">
            <h3 id="next-step" class="sr-only">{{ __('Next step') }}</h3>

            @unless ($status === LicenceRequestStatus::Rejected)
                <ol class="grid gap-3 sm:grid-cols-3">
                    @foreach ($steps as $index => [$label, $isDone, $isCurrent, $detail])
                        <li class="flex items-start gap-3 text-sm" @if ($isCurrent) aria-current="step" @endif>
                            <span @class([
                                'flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-lime-600 text-white' => $isDone,
                                'bg-orange-600 text-white' => $isCurrent && ! $isDone,
                                'bg-zinc-100 text-zinc-500' => ! $isDone && ! $isCurrent,
                            ])>{{ $isDone ? '✓' : $index + 1 }}</span>
                            <span>
                                <span @class(['block', 'font-semibold text-zinc-900' => $isCurrent, 'text-zinc-900' => ! $isCurrent])>{{ $label }}</span>
                                <span class="block text-zinc-600">{{ $detail }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endunless

            <div class="flex flex-wrap items-center gap-3 border-t border-zinc-200 pt-4">
                @if ($isReviewable)
                    <flux:modal.trigger name="finish-review">
                        <x-button type="button">{{ $commentCount > 0 ? trans_choice('Request changes (:count comment)|Request changes (:count comments)', $commentCount) : __('Finish review') }}</x-button>
                    </flux:modal.trigger>
                    <p class="text-sm text-zinc-600">
                        {{ $commentCount > 0
                            ? __('You confirm the comments before they are sent to the driver. Remove them to mark the request as verified instead.')
                            : __('Mark each document as viewed and comment on what needs fixing, then finish the review.') }}
                    </p>
                @elseif ($status === LicenceRequestStatus::ChangesRequested)
                    <p class="text-sm text-zinc-600">{{ __('The driver is fixing what you commented on. The request comes back here once sent again.') }}</p>
                @elseif ($isVerified && $paymentStatus === LicenceRequestPaymentStatus::PaymentSubmitted)
                    <x-button type="button" wire:click="confirmPayment">{{ __('Confirm payment') }}</x-button>
                    <x-secondary-button wire:click="rejectPayment" wire:confirm="{{ __('Reject the payment proof? The driver is asked to upload a new one.') }}">{{ __('Reject proof') }}</x-secondary-button>
                    @if ($licenceRequest->latestPayment)
                        <a href="{{ $licenceRequest->latestPayment->downloadUrl }}" target="_blank" class="text-sm font-medium text-orange-600 hover:text-orange-900">{{ __('Open the payment proof') }}</a>
                    @endif
                @elseif ($isVerified && ! $isPaymentSettled)
                    <x-button type="button" disabled>{{ __('Emit licence') }}</x-button>
                    <p class="text-sm text-zinc-600">{{ __('Waiting for the driver\'s payment.') }}</p>
                    @if ($paymentStatus === LicenceRequestPaymentStatus::AwaitingPayment)
                        <flux:modal.trigger name="office-payment">
                            <x-secondary-button>{{ __('Paid at the office') }}</x-secondary-button>
                        </flux:modal.trigger>
                    @endif
                    @if ($licenceRequest->canBePriced())
                        <flux:modal.trigger name="change-amount">
                            <x-secondary-button>{{ __('Change amount') }}</x-secondary-button>
                        </flux:modal.trigger>
                    @endif
                @elseif ($isVerified)
                    <flux:modal.trigger name="emit-licence">
                        <x-button type="button">{{ __('Emit licence') }}</x-button>
                    </flux:modal.trigger>
                    @if ($status === LicenceRequestStatus::Verified)
                        <x-secondary-button wire:click="handToProvider">{{ __('Handed to :provider, waiting for the licence', ['provider' => $providerLabel]) }}</x-secondary-button>
                    @endif
                @elseif ($status === LicenceRequestStatus::Emitted && $licenceRequest->licence)
                    <x-button-link href="{{ route('licences.show', $licenceRequest->licence) }}">{{ __('Open the emitted licence') }}</x-button-link>
                @elseif ($status === LicenceRequestStatus::Rejected)
                    <p class="text-sm text-red-800"><span class="font-semibold">{{ __('Rejected') }}:</span> {{ $licenceRequest->rejection_reason }}</p>
                @endif

                @if ($licenceRequest->coveringOrder)
                    <a href="{{ route('orders.show', $licenceRequest->coveringOrder) }}" class="text-sm text-orange-600 hover:text-orange-900">{{ __('Order :order', ['order' => $licenceRequest->coveringOrder->ulid]) }}</a>
                @endif

                @if (in_array($status, LicenceRequestStatus::rejectable(), true))
                    <flux:modal.trigger name="reject-request">
                        <button type="button" class="ms-auto text-sm font-medium text-red-600 hover:text-red-900">{{ __('Reject request…') }}</button>
                    </flux:modal.trigger>
                @endif
            </div>
        </section>
    @endif

    <div @class(['grid gap-6', 'lg:grid-cols-2' => ! $isDraft])>
        <div class="space-y-4 min-w-0">
            <section class="{{ $cardClass }}">
                <h3 class="font-semibold text-zinc-800">{{ __('Documents and forms') }}</h3>
                @if ($newUploadCount > 0)
                    <p class="text-sm text-sky-800">{{ trans_choice(':count document replaced since you asked for changes|:count documents replaced since you asked for changes', $newUploadCount) }}</p>
                @endif

                @if ($licenceRequest->documents_pruned_at)
                    <p class="text-sm text-zinc-600">{{ __('Documents deleted on :date after the retention period.', ['date' => $licenceRequest->documents_pruned_at->setTimezone(config('races.timezone'))->isoFormat('D MMMM YYYY')]) }}</p>
                @else
                    <ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200">
                        @foreach ($requiredDocuments as $required)
                            @php
                                $upload = $licenceRequest->uploadFor($required);
                                $target = $upload ? 'document:'.$upload->getKey() : null;
                                $isSelected = ! $isDraft && $upload !== null && $selectedDocument?->is($upload);
                                $isFlagged = $upload?->status === LicenceRequestDocumentStatus::Flagged;
                                $isViewed = $upload?->status === LicenceRequestDocumentStatus::Accepted;
                                $providerFormUrl = LicenceCatalogue::formUrl($licenceRequest->provider, $required->document);
                                [$badge, $badgeLabel] = match (true) {
                                    $upload === null => ['missing', __('Not uploaded yet')],
                                    $isFlagged => ['comment', __('Needs changes')],
                                    $isViewed => ['viewed', __('Viewed')],
                                    $upload->isUploadedAfter($changesRequestedAt) => ['new', __('New file')],
                                    default => ['todo', __('To review')],
                                };
                            @endphp
                            <li wire:key="document-{{ $required->key() }}" @class(['p-3 space-y-2', 'bg-orange-50' => $isSelected])>
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                    @if ($upload && ! $isDraft)
                                        <button type="button" wire:click="selectDocument({{ $upload->getKey() }})" class="flex-1 min-w-40 text-left font-medium text-zinc-900 hover:text-orange-700" @if ($isSelected) aria-current="true" @endif>
                                            {{ $required->document->label() }}
                                            @if ($required->parentIndex !== null)
                                                <span class="font-normal text-zinc-600">&middot; {{ ($parents[$required->parentIndex] ?? null)?->fullName() }}</span>
                                            @endif
                                            <span class="block text-xs font-normal text-orange-600">{{ $isSelected ? __('Shown on the right') : __('View') }}</span>
                                        </button>
                                    @else
                                        <span class="flex-1 min-w-40 font-medium text-zinc-900">
                                            {{ $required->document->label() }}
                                            @if ($required->parentIndex !== null)
                                                <span class="font-normal text-zinc-600">&middot; {{ ($parents[$required->parentIndex] ?? null)?->fullName() }}</span>
                                            @endif
                                        </span>
                                    @endif

                                    <span class="inline-block px-2 rounded-full text-xs font-medium {{ $badgeClasses[$badge] }}">{{ $badgeLabel }}</span>

                                    @if ($providerFormUrl)
                                        <a href="{{ $providerFormUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 {{ $commentButtonClass }}">
                                            {{ __('Original from :provider', ['provider' => $providerLabel]) }}
                                            <flux:icon.arrow-top-right-on-square variant="micro" aria-hidden="true" />
                                            <span class="sr-only">{{ __('(opens the provider\'s website)') }}</span>
                                        </a>
                                    @endif

                                    @if ($upload && $isReviewable && ! $isFlagged)
                                        <label class="inline-flex cursor-pointer items-center gap-1.5 text-zinc-700">
                                            <input type="checkbox" wire:click="toggleViewed({{ $upload->getKey() }})" @checked($isViewed) class="rounded-sm border-zinc-300 text-orange-600 focus:ring-orange-300">
                                            {{ __('Viewed') }}
                                        </label>
                                        @if ($commentingOn !== $target)
                                            <button type="button" wire:click="startComment('{{ $target }}')" class="{{ $commentButtonClass }}">{{ __('Comment…') }}</button>
                                        @endif
                                    @endif
                                </div>

                                @if ($isFlagged)
                                    <div class="flex flex-wrap items-start gap-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                                        <p class="flex-1 min-w-40"><span class="font-semibold">{{ __('Comment') }}:</span> {{ $upload->note }}</p>
                                        @if ($isReviewable)
                                            <button type="button" wire:click="removeDocumentComment({{ $upload->getKey() }})" class="font-medium text-red-700 hover:text-red-900">{{ __('Remove') }}</button>
                                        @endif
                                    </div>
                                @elseif ($upload?->note)
                                    <p class="text-sm text-zinc-500"><span class="font-semibold">{{ __('Your previous comment') }}:</span> {{ $upload->note }}</p>
                                @endif

                                @if ($target)
                                    @include('livewire.licence-request-review.comment-form', ['target' => $target])
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @include('livewire.licence-request-review.comments', ['sections' => [LicenceRequestSection::Documents, LicenceRequestSection::Forms], 'target' => null])
            </section>

            <section class="{{ $cardClass }}">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="flex-1 font-semibold text-zinc-800">{{ LicenceRequestSection::Licence->label() }}</h3>
                    @if ($isReviewable && $commentingOn !== 'section:licence')
                        <button type="button" wire:click="startComment('section:licence')" class="{{ $commentButtonClass }}">{{ __('Comment…') }}</button>
                    @endif
                </div>
                <dl class="grid grid-cols-3 gap-x-4 gap-y-1 text-sm">
                    <dt class="text-zinc-500">{{ __('Provider') }}</dt>
                    <dd class="col-span-2">{{ $providerLabel ?? '—' }}</dd>
                    <dt class="text-zinc-500">{{ __('Licence type') }}</dt>
                    <dd class="col-span-2">{{ $licenceRequest->licenceTypeData()?->name ?? $licenceRequest->licence_type ?? '—' }}</dd>
                    @if ($licenceRequest->licenceTypeData()?->allowsCompetitor())
                        <dt class="text-zinc-500">{{ __('Competitor licence') }}</dt>
                        <dd class="col-span-2">{{ $licenceRequest->includes_competitor ? __('Yes') : __('No') }}</dd>
                    @endif
                    <dt class="text-zinc-500">{{ __('Last year\'s licence number') }}</dt>
                    <dd class="col-span-2 font-mono break-all">{{ $licenceRequest->previous_licence_number ?? '—' }}</dd>
                </dl>
                @if ($previousLicenceOfAnotherUser)
                    <p class="rounded-md border border-amber-400 bg-amber-50 px-2 py-1 text-sm text-amber-800">
                        {{ __('This number belongs to a licence managed by another user.') }}
                        <a href="{{ route('licences.show', $previousLicenceOfAnotherUser) }}" class="underline">{{ __('Open it') }}</a>
                    </p>
                @endif
                @include('livewire.licence-request-review.comments', ['sections' => [LicenceRequestSection::Licence], 'target' => 'section:licence'])
            </section>

            @if ($licenceRequest->holder)
                <section class="{{ $cardClass }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="flex-1 font-semibold text-zinc-800">{{ LicenceRequestSection::Holder->label() }}</h3>
                        @if ($isReviewable && $commentingOn !== 'section:holder')
                            <button type="button" wire:click="startComment('section:holder')" class="{{ $commentButtonClass }}">{{ __('Comment…') }}</button>
                        @endif
                    </div>
                    @include('livewire.licence-request-review.person', ['person' => $licenceRequest->holder])
                    @include('livewire.licence-request-review.comments', ['sections' => [LicenceRequestSection::Holder], 'target' => 'section:holder'])
                </section>
            @endif

            @if ($parents !== [])
                <section class="{{ $cardClass }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="flex-1 font-semibold text-zinc-800">{{ LicenceRequestSection::Parents->label() }}</h3>
                        @if ($isReviewable && $commentingOn !== 'section:parents')
                            <button type="button" wire:click="startComment('section:parents')" class="{{ $commentButtonClass }}">{{ __('Comment…') }}</button>
                        @endif
                    </div>
                    @foreach ($parents as $parentIndex => $parent)
                        <div class="space-y-1">
                            <h4 class="text-sm font-medium text-zinc-700">{{ __('Parent or guardian :number', ['number' => $parentIndex + 1]) }}</h4>
                            @include('livewire.licence-request-review.person', ['person' => $parent])
                        </div>
                    @endforeach
                    @include('livewire.licence-request-review.comments', ['sections' => [LicenceRequestSection::Parents], 'target' => 'section:parents'])
                </section>
            @endif
        </div>

        @unless ($isDraft)
            <div class="min-w-0 lg:sticky lg:top-4 lg:self-start">
                @if ($selectedDocument)
                    @php
                        $documentUrl = route('licence-requests.documents.show', [$licenceRequest, $selectedDocument]);
                    @endphp
                    <div wire:key="viewer-{{ $selectedDocument->getKey() }}-{{ $selectedDocument->hash }}" class="rounded-md border border-zinc-200 bg-white p-2 space-y-2">
                        <p class="text-sm text-zinc-700 flex flex-wrap gap-2">
                            <span class="flex-1 font-medium">{{ $selectedDocument->document->label() }} &middot; {{ $selectedDocument->original_name }}</span>
                            <a href="{{ $documentUrl }}" target="_blank" class="text-orange-600 hover:text-orange-900">{{ __('Open in a new tab') }}</a>
                        </p>
                        @if (str_starts_with($selectedDocument->mime, 'image/'))
                            <img src="{{ $documentUrl }}" alt="{{ $selectedDocument->document->label() }}" class="w-full rounded-sm border border-zinc-200">
                        @else
                            <iframe src="{{ $documentUrl }}" class="w-full h-[70vh] rounded-sm border border-zinc-200" title="{{ $selectedDocument->document->label() }}"></iframe>
                        @endif
                    </div>
                @else
                    <p class="rounded-md border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500">{{ __('No document uploaded yet.') }}</p>
                @endif
            </div>
        @endunless
    </div>

    @if ($isReviewable)
        <flux:modal name="finish-review" class="max-w-lg">
            <div class="space-y-6">
                <flux:heading size="lg">{{ __('Finish your review') }}</flux:heading>
                <x-input-error for="request" />

                <form wire:submit="requestChanges" class="space-y-3">
                    <h4 class="font-medium text-zinc-900">{{ __('Request changes') }}</h4>
                    @if ($commentCount > 0)
                        <p class="text-sm text-zinc-600">{{ __('The driver gets these comments and sends the request back once fixed.') }}</p>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($openFlags as $flag)
                                <li><span class="font-semibold">{{ $flag->section->label() }}:</span> {{ $flag->note }}</li>
                            @endforeach
                            @foreach ($documentComments as $document)
                                <li><span class="font-semibold">{{ $document->document->label() }}:</span> {{ $document->note }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-zinc-600">{{ __('Comment on a document or section first.') }}</p>
                    @endif
                    <x-button :disabled="$commentCount === 0">{{ trans_choice('Request changes (:count comment)|Request changes (:count comments)', $commentCount) }}</x-button>
                </form>

                <form wire:submit="verify" class="space-y-3 border-t border-zinc-200 pt-6">
                    <h4 class="font-medium text-zinc-900">{{ __('Mark as verified') }}</h4>
                    @if ($commentCount > 0)
                        <p class="text-sm text-zinc-600">{{ __('Remove the comments or request changes.') }}</p>
                    @elseif ($viewedCount < $documentCount)
                        <p class="text-sm text-zinc-600">{{ __('Mark every document as viewed first: :viewed of :total viewed.', ['viewed' => $viewedCount, 'total' => $documentCount]) }}</p>
                    @elseif ($paymentStatus === LicenceRequestPaymentStatus::Covered)
                        <p class="text-sm text-zinc-600">{{ __('A package covers the request: nothing to pay.') }}</p>
                    @else
                        <p class="text-sm text-zinc-600">{{ __('The driver is asked to pay this amount. You emit the licence once the payment is confirmed.') }}</p>
                        <div>
                            <x-label for="amount" :value="__('Amount (€)').'*'" />
                            <x-input id="amount" type="text" inputmode="decimal" wire:model="amount" class="mt-1 block w-32" required />
                            <x-input-error for="amount" />
                        </div>
                    @endif
                    <x-button :disabled="! $canVerify">{{ __('Mark as verified') }}</x-button>
                </form>
            </div>
        </flux:modal>
    @endif

    @if ($isVerified && $licenceRequest->canBePriced())
        <flux:modal name="change-amount" class="max-w-md">
            <form wire:submit="changeAmount" class="space-y-6">
                <flux:heading size="lg">{{ __('Change amount') }}</flux:heading>
                <div>
                    <x-label for="new_amount" :value="__('Amount (€)').'*'" />
                    <x-input id="new_amount" type="text" inputmode="decimal" wire:model="amount" class="mt-1 block w-32" required />
                    <x-input-error for="amount" />
                    <p class="mt-1 text-sm text-zinc-600">{{ __('The amount can change until the driver uploads a payment proof.') }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-secondary-button>{{ __('Cancel') }}</x-secondary-button>
                    </flux:modal.close>
                    <x-button>{{ __('Save amount') }}</x-button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if ($isVerified && $paymentStatus === LicenceRequestPaymentStatus::AwaitingPayment)
        <flux:modal name="office-payment" class="max-w-md">
            <form wire:submit="recordOfficePayment" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Paid at the office') }}</flux:heading>
                    <flux:subheading>{{ __('Record that the driver paid :amount at your office. You can then emit the licence.', ['amount' => $licenceRequest->amountLabel()]) }}</flux:subheading>
                </div>
                <x-input-error for="payment" />
                <fieldset class="space-y-2">
                    <legend class="text-sm font-medium text-zinc-700">{{ __('Paid by') }}*</legend>
                    @foreach ([PaymentChannelType::CASH, PaymentChannelType::CREDIT_CARD] as $channel)
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="radio" name="office_payment_channel" value="{{ $channel->value }}" wire:model="officePaymentChannel" class="text-orange-600 focus:ring-orange-500" required>
                            {{ $channel->localizedName() }}
                        </label>
                    @endforeach
                    <x-input-error for="officePaymentChannel" />
                </fieldset>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-secondary-button>{{ __('Cancel') }}</x-secondary-button>
                    </flux:modal.close>
                    <x-button>{{ __('Record payment') }}</x-button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if ($isVerified && $isPaymentSettled)
        <flux:modal name="emit-licence" class="max-w-lg">
            <form wire:submit="emit" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Emit licence') }}</flux:heading>
                    <flux:subheading>{{ __('Upload the temporary licence emitted by :provider. The driver finds it in My licences.', ['provider' => $providerLabel]) }}</flux:subheading>
                </div>
                <x-input-error for="request" />
                <div>
                    <x-label for="temporary_licence" :value="__('Temporary licence (PDF)').'*'" />
                    <x-input id="temporary_licence" type="file" wire:model="temporaryLicence" accept="application/pdf" class="mt-1 max-w-full" required />
                    <span wire:loading wire:target="temporaryLicence" class="text-sm text-zinc-600" role="status">{{ __('Uploading…') }}</span>
                    <x-input-error for="temporaryLicence" />
                    <x-input-error for="temporary_licence" />
                </div>
                <div class="flex flex-wrap gap-3">
                    <div class="flex-1 min-w-40">
                        <x-label for="number" :value="__('Licence number').'*'" />
                        <x-input id="number" type="text" wire:model="number" class="mt-1 block w-full" required />
                        <x-input-error for="number" />
                    </div>
                    <div>
                        <x-label for="validity_year" :value="__('Valid for the year').'*'" />
                        <x-input id="validity_year" type="number" wire:model="validityYear" class="mt-1 block w-28" required />
                        <x-input-error for="validityYear" />
                        <x-input-error for="validity_year" />
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-secondary-button>{{ __('Cancel') }}</x-secondary-button>
                    </flux:modal.close>
                    <x-button wire:loading.attr="disabled" wire:target="temporaryLicence,emit">{{ __('Emit licence') }}</x-button>
                </div>
            </form>
        </flux:modal>
    @endif

    @if (in_array($status, LicenceRequestStatus::rejectable(), true))
        <flux:modal name="reject-request" class="max-w-lg">
            <form wire:submit="reject" class="space-y-6">
                <div>
                    <flux:heading size="lg">{{ __('Reject this request?') }}</flux:heading>
                    <flux:subheading>{{ __('The request is closed without a licence. Refunds are settled by email.') }}</flux:subheading>
                </div>
                <div>
                    <x-label for="rejection_reason" :value="__('Reason, shown to the driver').'*'" />
                    <x-textarea id="rejection_reason" wire:model="rejectionReason" class="mt-1 block w-full" required />
                    <x-input-error for="rejection_reason" />
                </div>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-secondary-button>{{ __('Cancel') }}</x-secondary-button>
                    </flux:modal.close>
                    <x-danger-button type="submit">{{ __('Reject request') }}</x-danger-button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
