@php
    $selectClass = 'mt-1 block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<form method="GET" action="{{ route('races.registration.create', $race) }}" class="mb-6 space-y-4 rounded-md border border-zinc-200 bg-white p-4">
    <div>
        <h3 class="font-semibold text-zinc-900">{{ __('Fill in from your licences') }}</h3>
        <p class="text-sm text-zinc-600">{{ __('Pick the licences valid for :year: the form is filled in with their details, which you can still change.', ['year' => $race->event_start_at->year]) }}</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <x-label for="picker_driver_licence" value="{{ __('Driver') }}" />
            <select id="picker_driver_licence" name="driver_licence" class="{{ $selectClass }}">
                <option value="">{{ __('Someone else (fill in the form)') }}</option>
                @foreach ($licencePickers['drivers'] as $licence)
                    <option value="{{ $licence->ulid }}" @selected($licencePickers['driver']?->is($licence))>{{ $licence->holder->fullName() }} &middot; {{ $licence->licenceTypeLabel() }} &middot; {{ $licence->number }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-label for="picker_competitor_licence" value="{{ __('Competitor') }}" />
            <select id="picker_competitor_licence" name="competitor_licence" class="{{ $selectClass }}">
                <option value="">{{ __('As in the previous registration') }}</option>
                <option value="{{ \App\Actions\PrepareRegistrationLicencePickers::NO_COMPETITOR }}" @selected(request('competitor_licence') === \App\Actions\PrepareRegistrationLicencePickers::NO_COMPETITOR)>{{ __('No competitor licence') }}</option>
                @foreach ($licencePickers['competitors'] as $licence)
                    <option value="{{ $licence->ulid }}" @selected($licencePickers['competitor']?->is($licence))>{{ $licence->holder->fullName() }} &middot; {{ $licence->number }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-label for="picker_mechanic_licence" value="{{ __('Mechanic') }}" />
            <select id="picker_mechanic_licence" name="mechanic_licence" class="{{ $selectClass }}">
                <option value="">{{ __('No mechanic licence') }}</option>
                @foreach ($licencePickers['mechanics'] as $licence)
                    <option value="{{ $licence->ulid }}" @selected($licencePickers['mechanic']?->is($licence))>{{ $licence->holder->fullName() }} &middot; {{ $licence->number }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-secondary-button type="submit">{{ __('Fill in the form') }}</x-secondary-button>
</form>
