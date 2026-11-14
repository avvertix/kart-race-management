@use('App\Enums\LicenceRequestSection')
@use('App\Enums\LicenceRequestStatus')

@php
    $isChangesRequested = $licenceRequest->status === LicenceRequestStatus::ChangesRequested;
    $openChangeRequests = $licenceRequest->changeRequests->whereNull('resolved_at');
    $inputClass = 'mt-1 block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';

    $stateLabels = [
        'done' => __('Complete'),
        'todo' => __('To do'),
        'flagged' => __('Needs changes'),
        'fixed' => __('Fixed'),
        'accepted' => __('Accepted'),
    ];

    $stateClasses = [
        'done' => 'bg-lime-100 text-lime-800',
        'todo' => 'bg-zinc-100 text-zinc-700',
        'flagged' => 'bg-red-100 text-red-800',
        'fixed' => 'bg-lime-100 text-lime-800',
        'accepted' => 'bg-zinc-100 text-zinc-700',
    ];

    $leads = [
        'licence' => __('The licence to renew and the one you need this year.'),
        'holder' => __('The person the licence is for, as on their identity card.'),
        'parents' => __('The holder is underage: at least one parent or guardian is required.'),
        'documents' => __('Photos or PDFs. Files are only seen by the organizer.'),
        'forms' => __('Download each form, already filled in with the details, sign it and upload it.'),
    ];
@endphp

<x-slot name="header">
    <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex flex-wrap gap-2">
        <span><a href="{{ route('my-licences.index') }}">{{ __('My licences') }}</a></span>
        <span>/</span>
        <span><a href="{{ route('licence-requests.show', $licenceRequest) }}">{{ $licenceRequest->holder ? __('Licence request for :holder', ['holder' => $licenceRequest->holder->fullName()]) : __('Licence request') }}</a></span>
    </h2>
    <p class="text-sm text-zinc-500">
        {{ $licenceRequest->status->label() }}
        &middot; {{ $isChangesRequested ? __('Fix the flagged parts, everything else is accepted.') : __('Each section is saved when you continue. You can come back any time.') }}
    </p>
    <div class="mt-2">
        @include('partials.waiting-enrollments', ['licenceRequest' => $licenceRequest])
    </div>
</x-slot>

<div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8 max-w-3xl">

    @if ($isChangesRequested)
        <div class="mb-4 rounded-md border border-red-300 bg-red-50 p-3 text-sm text-red-800" role="status">
            @if ($licenceRequest->hasResolvedAllChanges())
                {{ __('All requested changes are fixed. Send the request back to the organizer.') }}
            @else
                {{ __('The organizer asked for changes. Fix the flagged sections and documents, then send the request back.') }}
            @endif
        </div>
    @endif

    <ol class="space-y-3">
        @foreach ($sections as $index => $section)
            @php
                $isOpen = $openSection === $section->value;
                $state = $this->sectionState($section);
                $canEdit = $licenceRequest->canEditSection($section);
                $notes = $openChangeRequests->where('section', $section);
            @endphp
            <li wire:key="section-{{ $section->value }}" @class(['rounded-md border bg-white', 'border-red-300' => $state === 'flagged', 'border-zinc-200' => $state !== 'flagged'])>
                <button type="button" wire:click="open('{{ $section->value }}')" wire:loading.delay.class="opacity-60" wire:target="open('{{ $section->value }}')" class="flex w-full items-start gap-3 p-4 text-left" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700">{{ $index + 1 }}</span>
                    <span class="flex-1 min-w-0">
                        <span class="block font-semibold text-zinc-900">{{ $section->label() }}</span>
                        <span class="block text-sm text-zinc-600 truncate">
                            @if ($isOpen)
                                {{ $leads[$section->value] }}
                            @else
                                <span class="inline-block px-2 rounded-full text-xs {{ $stateClasses[$state] }}">{{ $stateLabels[$state] }}</span>
                                {{ $this->sectionSummary($section) }}
                            @endif
                        </span>
                    </span>
                </button>

                @if ($isOpen)
                    <div class="border-t border-zinc-200 p-4 space-y-4">
                        @foreach ($notes as $note)
                            <p class="rounded-md bg-red-50 p-2 text-sm text-red-800" role="note"><span class="font-semibold">{{ __('Organizer') }}:</span> {{ $note->note }}</p>
                        @endforeach

                        @if (! $canEdit)
                            <p class="text-sm text-zinc-600">{{ __('Accepted by the organizer. No changes needed.') }}</p>
                            <p class="text-sm text-zinc-900">{{ $this->sectionSummary($section) }}</p>
                        @elseif ($section === LicenceRequestSection::Licence)
                            @if (count($providers) > 1)
                                <fieldset>
                                    <legend class="text-sm font-medium text-zinc-700">{{ __('Provider') }}*</legend>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($providers as $item)
                                            <label wire:key="provider-{{ $item->value }}" class="flex items-center gap-2 rounded-md border border-zinc-200 px-3 py-2 text-sm has-checked:border-orange-400 has-checked:bg-orange-50">
                                                <input type="radio" wire:model.live="provider" value="{{ $item->value }}" class="text-orange-600">
                                                {{ $item->label() }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error for="provider" class="mt-1" />
                                </fieldset>
                            @endif

                            @if ($provider !== '')
                                <div>
                                    <x-label for="licenceType" value="{{ __('Licence type') }}*" />
                                    <select id="licenceType" wire:model.live="licenceType" class="{{ $inputClass }}">
                                        <option value="">{{ __('Choose a licence type') }}</option>
                                        @foreach ($licenceTypes as $item)
                                            <option value="{{ $item->code }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-sm text-zinc-500">{{ __('The type of licence you are renewing.') }}</p>
                                    <x-input-error for="licenceType" class="mt-1" />
                                </div>

                                @if ($selectedLicenceType?->allowsCompetitor())
                                    <label class="flex items-start gap-2 text-sm">
                                        <input type="checkbox" wire:model="driverOnly" class="mt-0.5 rounded-sm border-zinc-300 text-orange-600">
                                        <span>{{ __('The licence is for the driver only') }}<span class="block text-zinc-500">{{ __('Leave it unchecked when the licence also covers the competitor.') }}</span></span>
                                    </label>
                                @endif

                                <div>
                                    <x-label for="previousLicenceNumber" :value="__('Last year\'s licence number').'*'" />
                                    <x-input id="previousLicenceNumber" type="text" wire:model="previousLicenceNumber" class="mt-1 block w-full" />
                                    <p class="mt-1 text-sm text-zinc-500">{{ __('As printed on the licence you are renewing.') }}</p>
                                    <x-input-error for="previousLicenceNumber" class="mt-1" />
                                </div>
                            @endif

                            <x-button type="button" wire:click="saveLicence">{{ __('Save and continue') }}</x-button>

                            <p class="text-sm text-zinc-500">
                                {{ __('For a first licence, write to the organizer:') }}
                                <a href="mailto:{{ config('races.organizer.email') }}" class="text-orange-600 hover:text-orange-900">{{ config('races.organizer.email') }}</a>
                            </p>
                        @elseif ($section === LicenceRequestSection::Holder)
                            @include('livewire.licence-request-form.person-fields', ['prefix' => 'holder'])

                            <x-button type="button" wire:click="saveHolder">{{ __('Save and continue') }}</x-button>
                        @elseif ($section === LicenceRequestSection::Parents)
                            <x-input-error for="parents" />

                            @foreach ($parents as $parentIndex => $parent)
                                <div wire:key="parent-{{ $parentIndex }}" class="space-y-3">
                                    <div class="flex items-center gap-2">
                                        <h3 class="flex-1 font-semibold text-zinc-800">
                                            {{ __('Parent or guardian :number', ['number' => $parentIndex + 1]) }}
                                            @if ($parentIndex > 0)
                                                <span class="font-normal text-zinc-500">({{ __('optional') }})</span>
                                            @endif
                                        </h3>
                                        @if ($parentIndex > 0)
                                            <button type="button" wire:click="removeParent({{ $parentIndex }})" class="text-sm text-red-600 hover:text-red-900">{{ __('Remove') }}</button>
                                        @endif
                                    </div>
                                    @include('livewire.licence-request-form.person-fields', ['prefix' => "parents.{$parentIndex}"])
                                </div>
                            @endforeach

                            @if (count($parents) < 2)
                                <x-secondary-button type="button" wire:click="addParent">
                                    {{ count($parents) === 0 ? __('Add a parent or guardian') : __('Add a second parent or guardian') }}
                                </x-secondary-button>
                            @endif

                            <div>
                                <x-button type="button" wire:click="saveParents">{{ __('Save and continue') }}</x-button>
                            </div>
                        @elseif ($section === LicenceRequestSection::Documents)
                            @include('livewire.licence-request-form.uploads', ['items' => $documents, 'isForm' => false])

                            <x-button type="button" wire:click="continue('documents')">{{ __('Continue') }}</x-button>
                        @elseif ($section === LicenceRequestSection::Forms)
                            @include('livewire.licence-request-form.uploads', ['items' => $forms, 'isForm' => true])

                            <x-button type="button" wire:click="continue('forms')">{{ __('Continue') }}</x-button>
                        @endif
                    </div>
                @endif
            </li>
        @endforeach

        <li wire:key="section-review" class="rounded-md border border-zinc-200 bg-white">
            <button type="button" wire:click="open('review')" class="flex w-full items-start gap-3 p-4 text-left" aria-expanded="{{ $openSection === 'review' ? 'true' : 'false' }}">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700">{{ count($sections) + 1 }}</span>
                <span class="flex-1">
                    <span class="block font-semibold text-zinc-900">{{ __('Review and submit') }}</span>
                    <span class="block text-sm text-zinc-600">{{ $isChangesRequested ? __('Send the fixes back to the organizer.') : __('Check everything once more.') }}</span>
                </span>
            </button>

            @if ($openSection === 'review')
                @php
                    $missing = $licenceRequest->missingSections();
                @endphp
                <div class="border-t border-zinc-200 p-4 space-y-4">
                    @if ($missing !== [])
                        <p class="rounded-md bg-zinc-50 p-2 text-sm text-zinc-700">
                            {{ __('Still missing: :sections.', ['sections' => collect($missing)->map->label()->join(', ')]) }}
                        </p>
                    @endif

                    @unless ($isChangesRequested)
                        @if ($renewalPrice !== null && $licenceRequest->covering_enrollment_id === null)
                            <p class="text-sm text-zinc-600">{{ __('The renewal costs :price.', ['price' => $renewalPrice]) }}</p>
                        @endif
                        <p class="text-sm text-zinc-600">{{ __('After you submit, the organizer checks the request and tells you the amount to pay.') }}</p>
                    @endunless

                    @if ($isChangesRequested)
                        @unless ($licenceRequest->hasResolvedAllChanges())
                            <p class="text-sm text-zinc-700">{{ __('Fix every flagged section and document before sending the request back.') }}</p>
                        @endunless
                    @else
                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" wire:model="declared" class="mt-0.5 rounded-sm border-zinc-300 text-orange-600">
                            <span>{{ __('I declare that the details and documents are true and belong to the holder.') }}</span>
                        </label>
                        <x-input-error for="declared" />
                    @endif

                    <x-input-error for="request" />

                    <x-button type="button" wire:click="submit" :disabled="$missing !== [] || ($isChangesRequested && ! $licenceRequest->hasResolvedAllChanges())">
                        {{ $isChangesRequested ? __('Send back to the organizer') : __('Submit request') }}
                    </x-button>
                </div>
            @endif
        </li>
    </ol>
</div>
