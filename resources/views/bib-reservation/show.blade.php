<x-app-layout>
    <x-slot name="title">
        {{ $reservation->bib }} - {{ __('Race Number Reservations') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">

        @section('actions')

            @can('update', $reservation)
                <x-button-link href="{{ route('bib-reservations.edit', $reservation) }}">
                    {{ __('Edit reservation') }}
                </x-button-link>
            @endcan

        @endsection

        @include('championship.partials.heading')
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-4">

            <div class="p-4 bg-white shadow-xl rounded space-y-2">
                <p class="text-2xl font-bold">
                    <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $reservation->bib }}</span> {{ $reservation->driver }}
                </p>
                <p>
                    <span class="block text-zinc-500">{{ __('Contact email')}}</span>
                    {{ $reservation->contact_email }}
                </p>
                <p>
                    <span class="block text-zinc-500">{{ __('Driver licence')}}</span>

                    @unless ($reservation->driver_licence)
                        <span class="block p-2 bg-yellow-300 rounded">
                            {{ __('Driver licence not specified. The reservation might not be enforced.') }}
                        </span>
                    @endunless

                    {{ $reservation->driver_licence }}
                    {{ $reservation->licence_type?->localizedName() }}
                </p>
                <p>
                    <span class="block text-zinc-500">{{ __('Reserved until')}}</span>
                    @if ($reservation->reservation_expires_at)
                        <x-time :value="$reservation->reservation_expires_at" />
                    @else
                        {{ __('end of championship') }}
                    @endif
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
