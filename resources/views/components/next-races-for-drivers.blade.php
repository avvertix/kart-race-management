<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">{{ __('Next races') }}</p>

    <div class="-mx-2 sm:mx-0 space-y-3">
        @forelse ($races as $race)
            <article class="p-2 md:p-4 bg-white rounded space-y-1">

                @if ($race->isCancelled() || $race->is_registration_open)
                    <p class="col-span-2 md:col-span-3 mb-1"><x-race-status :value="$race->status" /></p>
                @endif

                <h2 >
                    {{ $race->title }}
                </h2>

                <p class="text-sm text-zinc-700">{{ $race->period }} &middot; {{ $race->track }}</p>

                <div class="h-2"></div>

                @php $entries = $registrationsByRace->get($race->uuid, collect()); @endphp
                <div class="relative mt-1 flex flex-col gap-2">
                    @foreach ($entries as $entry)
                        @if ($entry['participation'])
                            <a href="{{ route('registration.show', $entry['participation']) }}"
                                class="text-zinc-700 hover:text-zinc-900 rounded px-3 py-2 group relative ring-0 hover:ring-2 focus-within:ring-2 ring-orange-500 bg-zinc-100 hover:bg-orange-100">
                                {{ __('View registration for :name', ['name' => $entry['linked']->first_name . ' ' . $entry['linked']->last_name]) }}
                            </a>
                        @elseif ($race->is_registration_open)
                            <a href="{{ route('races.registration.create', ['race' => $race, 'from' => $entry['linked']->uuid]) }}"
                                class="font-medium px-3 py-2 rounded group relative ring-0 hover:ring-2 focus-within:ring-2 ring-orange-500 bg-zinc-100 hover:bg-orange-100">
                                {{ __('Register :name', ['name' => $entry['linked']->first_name . ' ' . $entry['linked']->last_name]) }}
                            </a>
                        @endif
                    @endforeach
                    @if ($race->is_registration_open)
                        <a href="{{ route('races.registration.create', $race) }}"
                            class="font-medium px-3 py-2 rounded group relative ring-0 hover:ring-2 focus-within:ring-2 ring-orange-500 bg-zinc-100 hover:bg-orange-100">
                            {{ __('Register for the race') }}
                        </a>
                    @endif
                </div>

            </article>
        @empty
            <div class="p-2 md:p-4 bg-white rounded relative space-y-1">
                <p class="font-medium">{{ __('No race in sight') }}</p>
                <p class="text-zinc-700">{{ __('There are no races scheduled yet. Get back soon!') }}</p>
                <x-ri-steering-fill class="h-full w-auto text-zinc-200 absolute right-0 top-0" />
            </div>
        @endforelse
    </div>
</div>
