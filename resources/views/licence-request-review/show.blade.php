@use('App\Enums\LicenceDocumentKind')
@use('App\Enums\LicenceRequestDocumentStatus')
@use('App\Enums\LicenceRequestPaymentStatus')
@use('App\Enums\LicenceRequestSection')
@use('App\Enums\LicenceRequestStatus')

@php
    $status = $licenceRequest->status;
    $isReviewable = in_array($status, LicenceRequestStatus::underReview(), true);
    $isPaymentReviewable = in_array($status, LicenceRequestStatus::underPaymentReview(), true);
    $openFlags = $licenceRequest->changeRequests->whereNull('resolved_at');
    $flagCount = $openFlags->count() + $licenceRequest->documents->where('status', LicenceRequestDocumentStatus::Flagged)->count();
    $canBePriced = $licenceRequest->canBePriced();
    $changesRequestedAt = $licenceRequest->changes_requested_at;
    $isNew = fn ($required) => $licenceRequest->uploadFor($required)?->isUploadedAfter($changesRequestedAt) === true;
    $requiredDocuments = $licenceRequest->requiredDocuments(LicenceDocumentKind::Upload)
        ->merge($licenceRequest->requiredDocuments(LicenceDocumentKind::SignedForm))
        ->sortBy(fn ($required) => $isNew($required) ? 0 : 1)
        ->values();
    $newUploadCount = $requiredDocuments->filter($isNew)->count();
    $parents = $licenceRequest->parentHolders();
    $inputClass = 'mt-1 block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<x-app-layout>
    <x-slot name="title">
        {{ __('Licence request') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex flex-wrap gap-2">
            <span><a href="{{ route('licence-request-reviews.index') }}">{{ __('Licence requests') }}</a></span>
            <span>/</span>
            <span>{{ $licenceRequest->holder?->fullName() ?? __('Licence request') }}</span>
        </h2>
        <p class="text-sm text-zinc-500">
            {{ $status->label() }}
            &middot; {{ __('Requested by :user', ['user' => $licenceRequest->user->name]) }}
            @if ($licenceRequest->submitted_at)
                &middot; <x-time :value="$licenceRequest->submitted_at" />
            @endif
        </p>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8">
        <x-validation-errors class="mb-4" />

        <div class="grid gap-6 lg:grid-cols-2">

            {{-- Checklist --}}
            <div class="space-y-4 min-w-0">

                @if ($status === LicenceRequestStatus::Rejected && $licenceRequest->rejection_reason)
                    <p class="rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ $licenceRequest->rejection_reason }}</p>
                @endif

                @if ($licenceRequest->licence)
                    <p><a href="{{ route('licences.show', $licenceRequest->licence) }}" class="text-orange-600 hover:text-orange-900">{{ __('Open the emitted licence') }}</a></p>
                @endif

                <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                    <h3 class="font-semibold text-zinc-800">{{ LicenceRequestSection::Licence->label() }}</h3>
                    <dl class="grid grid-cols-3 gap-x-4 gap-y-1 text-sm">
                        <dt class="text-zinc-500">{{ __('Provider') }}</dt>
                        <dd class="col-span-2">{{ $licenceRequest->provider?->label() }}</dd>
                        <dt class="text-zinc-500">{{ __('Licence type') }}</dt>
                        <dd class="col-span-2">{{ $licenceRequest->licenceTypeData()?->name ?? $licenceRequest->licence_type }}</dd>
                        @if ($licenceRequest->licenceTypeData()?->allowsCompetitor())
                            <dt class="text-zinc-500">{{ __('Competitor licence') }}</dt>
                            <dd class="col-span-2">{{ $licenceRequest->includes_competitor ? __('Yes') : __('No') }}</dd>
                        @endif
                        <dt class="text-zinc-500">{{ __('Last year\'s licence number') }}</dt>
                        <dd class="col-span-2 font-mono break-all">{{ $licenceRequest->previous_licence_number }}</dd>
                    </dl>
                    @if ($previousLicenceOfAnotherUser)
                        <p class="rounded-md border border-amber-400 bg-amber-50 px-2 py-1 text-sm text-amber-800">
                            {{ __('This number belongs to a licence managed by another user.') }}
                            <a href="{{ route('licences.show', $previousLicenceOfAnotherUser) }}" class="underline">{{ __('Open it') }}</a>
                        </p>
                    @endif
                </section>

                @if ($licenceRequest->holder)
                    <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                        <h3 class="font-semibold text-zinc-800">{{ LicenceRequestSection::Holder->label() }}</h3>
                        @include('licence-request-review.partials.person', ['person' => $licenceRequest->holder])
                    </section>
                @endif

                @foreach ($parents as $parentIndex => $parent)
                    <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-2">
                        <h3 class="font-semibold text-zinc-800">{{ __('Parent or guardian :number', ['number' => $parentIndex + 1]) }}</h3>
                        @include('licence-request-review.partials.person', ['person' => $parent])
                    </section>
                @endforeach

                <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
                    <h3 class="font-semibold text-zinc-800">{{ __('Documents and forms') }}</h3>
                    @if ($newUploadCount > 0)
                        <p class="text-sm text-lime-800">{{ trans_choice(':count document replaced since you asked for changes|:count documents replaced since you asked for changes', $newUploadCount) }}</p>
                    @endif
                    <ul class="divide-y divide-zinc-200">
                        @foreach ($requiredDocuments as $required)
                            @php
                                $upload = $licenceRequest->uploadFor($required);
                                $isSelected = $upload !== null && $selectedDocument?->is($upload);
                                $isNewUpload = $upload?->isUploadedAfter($changesRequestedAt) === true;
                                $isUnchanged = $upload?->status === LicenceRequestDocumentStatus::Pending && $changesRequestedAt !== null && ! $isNewUpload;
                            @endphp
                            <li @class(['py-2 space-y-2', 'bg-orange-50' => $isSelected])>
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="flex-1 min-w-40 font-medium text-zinc-900">
                                        {{ $required->document->label() }}
                                        @if ($required->parentIndex !== null)
                                            <span class="font-normal text-zinc-600">&middot; {{ ($parents[$required->parentIndex] ?? null)?->fullName() }}</span>
                                        @endif
                                    </span>
                                    @if ($upload === null)
                                        <span class="text-zinc-500">{{ __('Not uploaded yet') }}</span>
                                    @elseif ($isNewUpload && $upload->status === LicenceRequestDocumentStatus::Pending)
                                        <span class="inline-block px-2 rounded-full bg-lime-100 text-xs text-lime-800">{{ __('New file') }}</span>
                                        <span class="text-zinc-500"><x-time :value="$upload->uploaded_at" /></span>
                                    @elseif ($isUnchanged)
                                        <span class="text-zinc-600">{{ __('Not reviewed yet') }}</span>
                                    @else
                                        <span class="text-zinc-600">{{ $upload->status->label() }}</span>
                                    @endif
                                    @if ($upload)
                                        <a href="{{ route('licence-request-reviews.show', [$licenceRequest, 'document' => $upload->getKey()]) }}" class="text-orange-600 hover:text-orange-900">{{ __('View') }}</a>
                                    @endif
                                </div>
                                @if ($upload?->note)
                                    @if ($upload->status === LicenceRequestDocumentStatus::Flagged)
                                        <p class="text-sm text-red-700"><span class="font-semibold">{{ __('Note') }}:</span> {{ $upload->note }}</p>
                                    @else
                                        <p class="text-sm text-zinc-500"><span class="font-semibold">{{ __('Your previous note') }}:</span> {{ $upload->note }}</p>
                                    @endif
                                @endif
                                @if ($upload && $isReviewable)
                                    <div class="flex flex-wrap items-start gap-2">
                                        <form method="POST" action="{{ route('licence-request-reviews.documents.update', [$licenceRequest, $upload]) }}">
                                            @method('PUT')
                                            @csrf
                                            <input type="hidden" name="decision" value="accept">
                                            <x-secondary-button type="submit">{{ __('Accept') }}</x-secondary-button>
                                        </form>
                                        <form method="POST" action="{{ route('licence-request-reviews.documents.update', [$licenceRequest, $upload]) }}" class="flex flex-1 flex-wrap gap-2 min-w-48">
                                            @method('PUT')
                                            @csrf
                                            <input type="hidden" name="decision" value="flag">
                                            <input type="text" name="note" required maxlength="1000" placeholder="{{ __('What is wrong?') }}" aria-label="{{ __('Note for :document', ['document' => $required->document->label()]) }}" class="flex-1 min-w-32 rounded-md border-zinc-300 text-sm">
                                            <x-secondary-button type="submit">{{ __('Flag') }}</x-secondary-button>
                                        </form>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
                    <h3 class="font-semibold text-zinc-800">{{ __('Flags for the requester') }}</h3>
                    @forelse ($openFlags as $flag)
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="flex-1"><span class="font-semibold">{{ $flag->section->label() }}:</span> {{ $flag->note }}</span>
                            @if ($isReviewable)
                                <form method="POST" action="{{ route('licence-request-reviews.flags.destroy', [$licenceRequest, $flag]) }}">
                                    @method('DELETE')
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Remove') }}</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-600">{{ __('No section flagged.') }}</p>
                    @endforelse

                    @if ($isReviewable)
                        <form method="POST" action="{{ route('licence-request-reviews.flags.store', $licenceRequest) }}" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div>
                                <x-label for="flag_section" value="{{ __('Section') }}" />
                                <select id="flag_section" name="section" class="{{ $inputClass }}">
                                    @foreach ($licenceRequest->sections() as $section)
                                        <option value="{{ $section->value }}">{{ $section->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-1 min-w-48">
                                <x-label for="flag_note" value="{{ __('Note') }}" />
                                <x-input id="flag_note" type="text" name="note" required maxlength="1000" class="mt-1 block w-full" />
                            </div>
                            <x-secondary-button type="submit">{{ __('Flag section') }}</x-secondary-button>
                        </form>
                    @endif
                </section>

                <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
                    <h3 class="font-semibold text-zinc-800">{{ __('Payment') }}</h3>
                    <p class="text-sm">
                        @if ($licenceRequest->amountLabel())
                            <span class="font-mono tabular-nums">{{ $licenceRequest->amountLabel() }}</span> &middot;
                        @endif
                        {{ $licenceRequest->payment_status->label() }}
                        @if ($licenceRequest->coveringOrder)
                            &middot; <a href="{{ route('orders.show', $licenceRequest->coveringOrder) }}" class="text-orange-600 hover:text-orange-900">{{ __('Order :order', ['order' => $licenceRequest->coveringOrder->ulid]) }}</a>
                        @endif
                    </p>

                    @if ($isPaymentReviewable && $canBePriced)
                        <form method="POST" action="{{ route('licence-request-reviews.amount', $licenceRequest) }}" class="flex flex-wrap items-end gap-2">
                            @method('PUT')
                            @csrf
                            <div>
                                <x-label for="amount" value="{{ __('Amount (€)') }}*" />
                                <x-input id="amount" type="text" inputmode="decimal" name="amount" :value="old('amount', $licenceRequest->amount === null ? null : number_format($licenceRequest->amount / 100, 2, '.', ''))" class="mt-1 block w-32" required />
                            </div>
                            <x-button type="submit">{{ __('Set amount') }}</x-button>
                        </form>
                        <p class="text-sm text-zinc-600">{{ __('The amount can change until a payment proof is uploaded.') }}</p>
                    @endif

                    @if ($licenceRequest->latestPayment)
                        <p class="text-sm text-zinc-600">
                            {{ __('Uploaded on') }} <x-time :value="$licenceRequest->latestPayment->created_at" />
                            &middot; <a href="{{ $licenceRequest->latestPayment->downloadUrl }}" target="_blank" class="text-orange-600 hover:text-orange-900">{{ __('Open in a new tab') }}</a>
                        </p>
                    @endif

                    @if ($isPaymentReviewable && $licenceRequest->payment_status === LicenceRequestPaymentStatus::PaymentSubmitted)
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('licence-request-reviews.payment', $licenceRequest) }}">
                                @csrf
                                <input type="hidden" name="decision" value="confirm">
                                <x-button type="submit">{{ __('Confirm payment') }}</x-button>
                            </form>
                            <form method="POST" action="{{ route('licence-request-reviews.payment', $licenceRequest) }}">
                                @csrf
                                <input type="hidden" name="decision" value="reject">
                                <x-secondary-button type="submit">{{ __('Reject payment') }}</x-secondary-button>
                            </form>
                        </div>
                    @endif
                </section>

                <section class="rounded-md border border-zinc-200 bg-white p-4 space-y-3">
                    <h3 class="font-semibold text-zinc-800">{{ __('Actions') }}</h3>

                    @if ($isReviewable)
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('licence-request-reviews.request-changes', $licenceRequest) }}">
                                @csrf
                                <x-secondary-button type="submit" :disabled="$flagCount === 0">{{ __('Request changes (:count)', ['count' => $flagCount]) }}</x-secondary-button>
                            </form>
                            <form method="POST" action="{{ route('licence-request-reviews.forward', $licenceRequest) }}">
                                @csrf
                                <x-button type="submit">{{ __('Mark as forwarded') }}</x-button>
                            </form>
                        </div>
                        <p class="text-sm text-zinc-600">{{ __('Forward once the payment is confirmed and nothing is flagged. Open each document to send it to the provider.') }}</p>
                    @elseif ($status === LicenceRequestStatus::ChangesRequested)
                        <p class="text-sm text-zinc-600">{{ __('Waiting for the requester to fix the flagged parts.') }}</p>
                    @endif

                    @if ($status === LicenceRequestStatus::Forwarded)
                        <form method="POST" action="{{ route('licence-request-reviews.emit', $licenceRequest) }}" enctype="multipart/form-data" class="space-y-3 rounded-md border border-zinc-200 p-3">
                            @csrf
                            <h4 class="font-medium text-zinc-900">{{ __('Emit licence') }}</h4>
                            <div>
                                <x-label for="temporary_licence" value="{{ __('Temporary licence (PDF)') }}*" />
                                <input id="temporary_licence" type="file" name="temporary_licence" accept="application/pdf" required class="mt-1 text-sm max-w-full">
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <div class="flex-1 min-w-40">
                                    <x-label for="number" value="{{ __('Licence number') }}*" />
                                    <x-input id="number" type="text" name="number" :value="old('number', $suggestedNumber)" class="mt-1 block w-full" required />
                                </div>
                                <div>
                                    <x-label for="validity_year" value="{{ __('Valid for the year') }}*" />
                                    <x-input id="validity_year" type="number" name="validity_year" :value="old('validity_year', $suggestedValidityYear)" class="mt-1 block w-28" required />
                                </div>
                            </div>
                            <x-button type="submit">{{ __('Emit licence') }}</x-button>
                        </form>
                    @endif

                    @if (in_array($status, LicenceRequestStatus::rejectable(), true))
                        <form method="POST" action="{{ route('licence-request-reviews.reject', $licenceRequest) }}" class="space-y-2">
                            @csrf
                            <x-label for="rejection_reason" value="{{ __('Reason, shown to the requester') }}" />
                            <x-textarea id="rejection_reason" name="rejection_reason" class="block w-full" required>{{ old('rejection_reason') }}</x-textarea>
                            <x-danger-button type="submit">{{ __('Reject request') }}</x-danger-button>
                        </form>
                    @endif
                </section>
            </div>

            {{-- Viewer --}}
            <div class="min-w-0 lg:sticky lg:top-4 lg:self-start">
                @if ($selectedDocument)
                    @php
                        $documentUrl = route('licence-requests.documents.show', [$licenceRequest, $selectedDocument]);
                    @endphp
                    <div class="rounded-md border border-zinc-200 bg-white p-2 space-y-2">
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
        </div>
    </div>
</x-app-layout>
