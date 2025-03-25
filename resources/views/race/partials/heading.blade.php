<div class="relative border-b-2 border-zinc-200 pb-5 sm:pb-0 print:hidden">
                <p class="">
                    <a href="{{ route('championships.show', $championship) }}" class="inline-flex gap-1 items-center hover:text-orange-600 focus:text-orange-600"><x-ri-trophy-line class="size-4 shrink-0" /> {{ $championship->title }}</a>
                </p>
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-bold text-xl md:text-2xl text-zinc-800 leading-tight">
                    {{ $race->title }}
                </h2>
                <div class="mt-3 flex md:absolute md:top-3 md:right-0 md:mt-0 gap-2  print:hidden">

                    @can('create', \App\Model\Participant::class)
                        <x-button-link href="{{ route('races.participants.create', $race) }}">
                            {{ __('Add participant') }}
                        </x-button-link>
                    @endcan

                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <x-button >
                                {{ __('Export or print') }}

                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </x-button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="p-2 w-60 flex flex-col gap-2">
                                @can('update', $race)
                                    <x-button-link href="{{ route('races.export.participants', $race) }}">
                                        {{ __('Export participants') }}
                                    </x-button-link>
                                    <x-button-link href="{{ route('races.participants.print', $race) }}">
                                        {{ __('Print participants') }}
                                    </x-button-link>
                                @endcan
                                @can('create', \App\Model\Transponder::class)
                                    <x-button-link href="{{ route('races.export.transponders', $race) }}">
                                        {{ __('Export transponders') }}
                                    </x-button-link>
                                @endcan
                            </div>
                        </x-slot>
                    </x-dropdown>

                    
                    @can('update', $race)
                        <x-button-link href="{{ route('races.edit', $race) }}">
                            {{ __('Edit race') }}
                        </x-button-link>
                    @endcan
                </div>
            </div>
            <div class="mt-2 hidden md:flex flex-wrap items-center gap-3 md:gap-6 text-sm text-zinc-500">

                 <p class="flex items-center gap-2">
                    <x-ri-calendar-2-line class="size-5 text-zinc-400 shrink-0" />
                    {{ $race->period }}
                </p>

                <p class="flex items-center gap-2">
                    <x-ri-map-pin-line class="size-5 text-zinc-400 shrink-0" />
                    {{ $race->track }}
                </p>
            </div>
            <div class="mt-6  print:hidden">
                    
                <div class="hidden sm:block">
                    <nav class="-mb-0.5 flex space-x-8">

                        <x-tab-link href="{{ route('races.show', $race) }}" :active="request()->routeIs('races.show', $race)">{{ __('Summary') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.participants.index', $race) }}"  :active="request()->routeIs('races.participants.index', $race)">{{ __('Participants') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.tires', $race) }}"  :active="request()->routeIs('races.tires', $race)">{{ __('Tires') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.transponders', $race) }}"  :active="request()->routeIs('races.transponders', $race)">{{ __('Transponders') }}</x-tab-link>
                        
                        <x-tab-link href="#" class="cursor-not-allowed">{{ __('Results') }}</x-tab-link>
                        
                    </nav>
                </div>
            </div>
        </div>
