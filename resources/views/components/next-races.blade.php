<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">{{ __('Next races') }}</p>

    <div class="-mx-2 sm:mx-0 space-y-3">
        @forelse ($races as $race)
            <article class="p-2 md:p-4 bg-white rounded group relative ring-0 hover:ring-2 focus-within:ring-2 ring-orange-500 space-y-1">

                @if ($race->isCancelled())
                    <p class="col-span-2 md:col-span-3"><x-race-status :value="$race->status" /></p>
                @endif

                <a href="{{ route( $canView ? 'races.show' : 'races.registration.create', $race) }}"
                    class="col-span-2 md:col-span-3 font-bold group-hover:text-orange-900 focus:text-orange-900 focus:outline-none">
                    <span class="z-10 absolute inset-0"></span>{{ $race->title }}
                </a>

                <p class="text-sm text-zinc-700">{{ $race->period }} &middot; {{ $race->track }}</p>

                @if ($canView)
                    <p class="text-sm text-zinc-700 tabular-nums">
                        @if ($race->hasTotalParticipantLimit())
                            {{ trans_choice(':value/:available participant|:value/:available participants', $race->participants_count, ['value' => $race->participants_count, 'available' => $race->getTotalParticipantLimit()]) }}
                        @else
                            {{ trans_choice(':value participant|:value participants', $race->participants_count, ['value' => $race->participants_count]) }}
                        @endif
                    </p>
                @endif


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
