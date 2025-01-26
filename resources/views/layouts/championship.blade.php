<x-app-layout>

    <x-slot name="title">
        {{ $title }}
    </x-slot>
    
    <x-slot name="header">
        <div class="relative border-b-2 border-zinc-200 pb-5 sm:pb-0">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                    {{ $championship->title }}
                </h2>

                @if (isset($actions))
                    <div {{ $actions->attributes->class(['mt-3','flex','md:absolute','md:top-3','md:right-0','md:mt-0','gap-2']) }}>
                        {{ $actions }}
                    </div>
                @endif

            </div>
            <div class="mt-2 flex items-center text-sm text-zinc-500">
                <div>
                    <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                    </svg>
                </div>
                {{ __('Period') }} {{ $championship->period }}
            </div>
            <div class="mt-6">

                <nav class="-mb-0.5 flex space-x-8">
                    
                    <x-tab-link href="{{ route('championships.show', $championship) }}" :active="request()->routeIs('championships.show', $championship)">{{ __('Races') }}</x-tab-link>
                    
                    <x-tab-link href="{{ route('championships.participants.index', $championship) }}" :active="request()->routeIs('championships.participants.index', $championship)">{{ __('Participants') }}</x-tab-link>
                    
                    <x-tab-link href="{{ route('championships.bib-reservations.index', $championship) }}" :active="request()->routeIs('championships.bib-reservations.index', $championship) || request()->routeIs('bib-reservations.*')">{{ __('Race Number Reservations') }}</x-tab-link>
                    
                    <x-tab-link href="{{ route('championships.bonuses.index', $championship) }}" :active="request()->routeIs('championships.bonuses.index', $championship) || request()->routeIs('bonuses.*')">{{ __('Bonus') }}</x-tab-link>

                    <x-tab-link href="{{ route('championships.categories.index', $championship) }}" :active="request()->routeIs('championships.categories.index', $championship) || request()->routeIs('categories.*')">{{ __('Categories') }}</x-tab-link>
                    
                    <x-tab-link href="{{ route('championships.tire-options.index', $championship) }}" :active="request()->routeIs('championships.tire-options.*') || request()->routeIs('tire-options.*')">{{ __('Tires') }}</x-tab-link>

                </nav>
            </div>       
        </div>

    </x-slot>


    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">

        {{ $slot }}
        
    </div>
</x-app-layout>
