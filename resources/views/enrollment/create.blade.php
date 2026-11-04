@php
    $selectClass = 'mt-1 block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Enroll a licence') }} - {{ $championship->title }}
    </x-slot>

    <x-validation-errors class="mb-4" />

    <form method="POST" action="{{ route('championships.enrollments.store', $championship) }}">
        @csrf

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Enroll a licence') }}</x-slot>
                <x-slot name="description">
                    {{ __('Enroll a driver licence valid for :year on behalf of its driver, also outside the enrollment period. No order is created: settle the payment with the driver.', ['year' => $championship->start_at->year]) }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 space-y-4">
                    <div>
                        <x-label for="licence" value="{{ __('Licence') }}" />
                        <select id="licence" name="licence" class="{{ $selectClass }}" required>
                            <option value="">{{ __('Choose a licence') }}</option>
                            @foreach ($licences as $licence)
                                <option value="{{ $licence->ulid }}" @selected(old('licence') === $licence->ulid)>{{ $licence->holder->fullName() }} &middot; {{ $licence->licenceTypeLabel() }} &middot; {{ $licence->number }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="licence" class="mt-2" />
                    </div>

                    <div>
                        <x-label for="competitor_licence" value="{{ __('Competitor') }}" />
                        <select id="competitor_licence" name="competitor_licence" class="{{ $selectClass }}">
                            <option value="">{{ __('No competitor') }}</option>
                            @foreach ($competitorLicences as $competitor)
                                <option value="{{ $competitor->ulid }}" @selected(old('competitor_licence') === $competitor->ulid)>{{ $competitor->holder->fullName() }} &middot; {{ $competitor->number }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="competitor_licence" class="mt-2" />
                    </div>

                    <x-button type="submit">{{ __('Enroll') }}</x-button>
                </div>
            </div>
        </div>
    </form>
</x-championship-page-layout>
