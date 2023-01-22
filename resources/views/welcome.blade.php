<x-app-layout>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
              
            <div>
                <p class="uppercase tracking-widest text-sm mb-2">{{ __('Register to a race') }}</p>
            
                <div class="grid grid-cols-3">
            
                    @forelse ($races as $race)
                        <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 shadow-orange-200">
                            <h1 class="text-2xl font-bold mb-1"><a href="{{ route('races.registration.create', $race) }}" class="text-orange-600 hover:text-orange-900">{{ $race->title }}</a></h1>
                            <p class="text-zinc-700 mb-1">{{ $race->period }}</p>
                            <p class="text-zinc-700">{{ $race->track }}</p>
                            <p class="text-zinc-700">{{ $race->status }}</p>
                        </article>
                    @empty
                        <article class="p-4 shadow-lg bg-white rounded-md ring-2 ring-orange-300 ">
                            <p class="text-zinc-700 mb-1">{{ __('No race currently available for registration. Race registration opens 7 days before the race.') }}</p>
                        </article>
                    @endforelse
                </div>
            
            </div>
            
        </div>
    </div>
</x-app-layout>
