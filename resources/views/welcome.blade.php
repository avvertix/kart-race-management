<x-app-layout>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">
              
            <div>
                <p class="uppercase tracking-widest text-sm mb-2">{{ __('Register to a race') }}</p>
            
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
                    @forelse ($races as $race)
                        <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 shadow-orange-200">
                            <h1 class="text-2xl font-bold mb-1"><a href="{{ route('races.registration.create', $race) }}" class="text-orange-600 hover:text-orange-900">{{ $race->title }}</a></h1>
                            <p class="text-zinc-700 mb-1">{{ $race->period }}</p>
                            <p class="text-zinc-700">{{ $race->track }}</p>

                            <p class="text-zinc-700 mt-2 -mx-4 -mb-4 rounded-b-md px-4 py-2 bg-orange-100 relative">
                                @if($race->isCancelled())
                                    <span class="absolute block w-full h-1 animate-pulse bg-red-300 inset-0"></span>
                                    {{ __('The race has been cancelled and registration is now closed.') }}
                                @elseif ($race->isRegistrationOpen)
                                    <span class="absolute block w-full h-1 animate-pulse bg-yellow-300 inset-0"></span>
                                    {{ __('Online registration open until') }} <x-time class="font-bold" :value="$race->registration_closes_at" :timezone="$race->timezone" />
                                @elseif( $race->status === 'active' || $race->status === 'scheduled')
                                    <span class="absolute block w-full h-1 animate-pulse bg-red-300 inset-0"></span>
                                    {{ __('Online registration closed. Registration might still be possible at the race track.') }}
                                @else
                                    {{ __('Online registration currently closed.') }}
                                @endif
                            </p>
                        </article>
                    @empty
                        <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 ">
                            <p class="text-zinc-700 mb-1">{{ __('No race currently available for registration. Race registration opens 6 days before the race.') }}</p>
                        </article>
                    @endforelse
                </div>

            </div>

            @if ($openChampionships->isNotEmpty())
                <div class="mt-10">
                    <p class="uppercase tracking-widest text-sm mb-2">{{ __('Enroll in a championship') }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($openChampionships as $championship)
                            @php($lowestOption = $championship->enrollmentOptions->sortBy('amount')->first())
                            <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 shadow-orange-200">
                                <h1 class="text-2xl font-bold mb-1"><a href="{{ route('public.championships.enrollment', $championship) }}" class="text-orange-600 hover:text-orange-900">{{ $championship->title }}</a></h1>
                                @if ($lowestOption)
                                    <p class="text-zinc-700 mb-1">
                                        {{ $championship->enrollmentOptions->count() > 1 ? __('From :price', ['price' => $lowestOption->currency->format($lowestOption->amount)]) : $lowestOption->currency->format($lowestOption->amount) }}
                                    </p>
                                @endif
                                <p class="text-zinc-700 mt-2 -mx-4 -mb-4 rounded-b-md px-4 py-2 bg-orange-100 relative">
                                    <span class="absolute block w-full h-1 animate-pulse bg-lime-300 inset-0"></span>
                                    {{ __('Enrollment open until') }} <x-time class="font-bold" :value="$championship->enrollment->closes_at" :timezone="config('races.timezone')" />
                                </p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($offersLicenceRenewal)
                <div class="mt-10">
                    <p class="uppercase tracking-widest text-sm mb-2">{{ __('Licence renewal') }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 shadow-orange-200">
                            <h1 class="text-2xl font-bold mb-1"><a href="{{ route('public.licence-renewal') }}" class="text-orange-600 hover:text-orange-900">{{ __('Renew your licence for :year', ['year' => $renewalYear]) }}</a></h1>
                            <p class="text-zinc-700">{{ __('Online, with the documents uploaded from home.') }}</p>
                            @if ($renewalPrice)
                                <p class="text-zinc-700 mt-2 -mx-4 -mb-4 rounded-b-md px-4 py-2 bg-orange-100">{{ __('Price') }}: <span class="font-bold">{{ $renewalPrice }}</span></p>
                            @endif
                        </article>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
