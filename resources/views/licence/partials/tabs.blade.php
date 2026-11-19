{{--
    Tabs between the licence requests and all licences, the organizer's Licences section.
    $requestsToProcess: how many licence requests wait for the organizer
--}}
<flux:navbar scrollable class="-mb-0.5 [--color-accent-content:var(--color-orange-600)]">
    @can('viewAny', \App\Models\LicenceRequest::class)
        <x-tab-link href="{{ route('licence-request-reviews.index') }}" :active="request()->routeIs('licence-request-reviews.*')">{{ __('Requests to process (:count)', ['count' => $requestsToProcess]) }}</x-tab-link>
    @endcan
    @can('viewAny', \App\Models\Licence::class)
        <x-tab-link href="{{ route('licences.index') }}" :active="request()->routeIs('licences.*')">{{ __('All licences') }}</x-tab-link>
    @endcan
</flux:navbar>
