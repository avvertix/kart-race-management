<x-app-layout>
    <x-slot name="title">
        {{ __('Dashboard') }}
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 grid md:grid-cols-12 gap-16">
        

        <div class="md:col-span-8 space-y-3">

            <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">{{ __('Next races') }}</p>


            <div class=" -mx-2 sm:mx-0 space-y-3">

                @forelse ($races as $race)
                    <article class="p-2 md:p-4 bg-white rounded group relative ring-0 hover:ring-2 focus-within:ring-2 ring-orange-500 grid grid-cols-2 md:grid-cols-3 gap-2">

                        @if ($race->isCancelled())

                            <p class="col-span-2 md:col-span-3 "><x-race-status :value="$race->status" /></p>

                        @endif
                        
                        <a href="{{ route('races.show', $race) }}" 
                            class="col-span-2 md:col-span-3 font-bold group-hover:text-orange-900 focus:text-orange-900 focus:outline-none">
                            <span class="z-10 absolute inset-0"></span>{{ $race->title }}

                        </a>
                        
                        
                        <p class="text-sm text-zinc-700 tabular-nums">
                            @if ($race->hasTotalParticipantLimit())
                                {{ trans_choice(':value/:available participant|:value/:available participants', $race->participants_count, ['value' => $race->participants_count, 'available' => $race->getTotalParticipantLimit()]) }}
                            @else
                                {{ trans_choice(':value participant|:value participants', $race->participants_count, ['value' => $race->participants_count]) }}
                            @endif
                        </p>
                        <p class="text-sm text-zinc-700">{{ $race->period }}</p>
                        <p class="hidden md:block text-sm text-zinc-700">{{ $race->track }}</p>
                    
                    </article>
                @empty
                    <div class="p-2 md:p-4 gap-3 bg-zinc-50 relative">
                        <x-ri-steering-fill class="h-full w-auto text-zinc-200 absolute right-0 top-0" />
                        <p class="text-zinc-700 font-medium mb-1 text-sm">{{ __('No race in sight.') }}</p>
                        <p class="text-zinc-700 text-sm">{{ __('No races scheduled yet.') }}</p>
                    </div>
                @endforelse
            
            </div>
            
        </div>

        <div class="hidden md:block col-span-4 space-y-3">
            

            <p data-section="true" class="font-mono text-xs/6 font-medium tracking-widest text-gray-600 uppercase">
                <a href="{{ route('championships.index') }}">{{ __('Championships') }}</a>
            </p>

            @forelse ($championships as $championship)
                <article class="p-2 md:p-4 bg-white rounded group relative ring-0 hover:ring-2 focus-within:ring-2 ring-orange-500 grid grid-cols-2 md:grid-cols-3 gap-2">
                    
                    <a href="{{ route('championships.show', $championship) }}" 
                        class="col-span-2 md:col-span-3 font-bold group-hover:text-orange-900 focus:text-orange-900 focus:outline-none">
                        <span class="z-10 absolute inset-0"></span>{{ $championship->title }}

                    </a>
                    
                    
                    <p class="text-sm text-zinc-700 tabular-nums">
                        {{ trans_choice(':value race|:value races', $championship->races_count, ['value' => $championship->races_count]) }}
                    </p>
                    <p class="text-sm text-zinc-700"></p>
                    <p class="hidden md:block text-sm text-zinc-700"></p>
                
                </article>
            @empty
                <div class="p-2 md:p-4 gap-3 bg-zinc-50 relative">
                    <x-ri-steering-fill class="h-full w-auto text-zinc-200 absolute right-0 top-0" />
                    <p class="text-zinc-700 font-medium mb-1 text-sm">{{ __('No championships.') }}</p>
                    <p class="text-zinc-700 text-sm">{{ __('No championships scheduled yet.') }}</p>
                </div>
            @endforelse
        </div>



    </div>

</x-app-layout>
