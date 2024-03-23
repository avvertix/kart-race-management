<x-app-layout>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              
            <div>
                <p class="uppercase tracking-widest text-sm mb-2">{{ __('Register to a race') }}</p>
            
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
                    @forelse ($races as $race)
                        <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 shadow-orange-200">
                            <h1 class="text-2xl font-bold mb-1"><a href="{{ route('races.registration.create', $race) }}" class="text-orange-600 hover:text-orange-900">{{ $race->title }}</a></h1>
                            <p class="text-zinc-700 mb-1">{{ $race->period }}</p>
                            <p class="text-zinc-700">{{ $race->track }}</p>

                            <p class="text-zinc-700 mt-2 -mx-4 -mb-4 rounded-b-md px-4 py-2 bg-orange-100 relative">
                                @if ($race->isRegistrationOpen)
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
            
        </div>
    </div>
</x-app-layout>
