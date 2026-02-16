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

                    <x-dropdown align="right" width="96">
                        <x-slot name="trigger">
                            <x-button >
                                {{ __('Export or print') }}

                                <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </x-button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="flex flex-col">
                                @can('update', $race)
                                    <a href="{{ route('races.export.participants', $race) }}" class="px-4 py-2 text-sm leading-5 text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:bg-zinc-100 transition">
                                        <span class="inline-flex gap-1">
                                            <x-ri-file-excel-line class="size-5 shrink-0" />
                                            {{ __('Export participants') }}
                                        </span>
                                        <span class="block ml-6 text-xs text-zinc-600">{{ __('Export all registered participants') }}</span>
                                    </a>
                                    <a href="{{ route('races.export.signature', $race) }}" class="px-4 py-2 text-sm leading-5 text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:bg-zinc-100 transition">
                                        <span class="inline-flex gap-1">
                                            <x-ri-sketching class="size-5 shrink-0" />
                                            {{ __('Export briefing signature module') }}
                                        </span>
                                        <span class="block ml-6 text-xs text-zinc-600">{{ __('Export form for signing the attendence of the briefing') }}</span>
                                    </a>
                                    @if ($race->isNationalOrInternational())
                                        <a href="{{ route('races.export.aci', $race) }}" class="px-4 py-2 text-sm leading-5 text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:bg-zinc-100 transition">
                                            <span class="inline-flex gap-1">
                                                <x-ri-table-2 class="size-5 shrink-0" />
                                                {{ __('Export for ACI Italian Cup') }}
                                            </span>
                                            <span class="block ml-6 text-xs text-zinc-600">{{ __('Export confirmed participants as requested by ACI Karting') }}</span>
                                        </a>
                                    @endif
                                    <div class="border-t border-zinc-100"></div>
                                    <a href="{{ route('races.participants.print', $race) }}" class="px-4 py-2 text-sm leading-5 text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:bg-zinc-100 transition">
                                        <span class="inline-flex gap-1">
                                            <x-ri-printer-line class="size-5 shrink-0" />
                                            {{ __('Print participants') }}
                                        </span>
                                        <span class="block ml-6 text-xs text-zinc-600">{{ __('Print all participants registrations') }}</span>
                                    </a>
                                    <a href="{{ route('races.participant-receipts.print', $race) }}" target="_blank" class="px-4 py-2 text-sm leading-5 text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:bg-zinc-100 transition">
                                        <span class="inline-flex gap-1">
                                            <x-ri-receipt-line class="size-5 shrink-0" />
                                            {{ __('Print receipts') }}
                                        </span>
                                        <span class="block ml-6 text-xs text-zinc-600">{{ __('Print receipts for all participants') }}</span>
                                    </a>
                                    <div class="border-t border-zinc-100"></div>
                                @endcan
                                @can('create', \App\Model\Transponder::class)
                                    <a href="{{ route('races.export.transponders', $race) }}" class="px-4 py-2 text-sm leading-5 text-zinc-700 hover:bg-zinc-100 focus:outline-none focus:bg-zinc-100 transition">
                                        <span class="inline-flex gap-1">
                                            <x-ri-steering-2-line class="size-5 shrink-0" />
                                            {{ __('Export transponders') }}
                                        </span>
                                        <span class="block ml-6 text-xs text-zinc-600">{{ __('Export drivers and transponder for MyLaps Orbits') }}</span>
                                    </a>
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

                @if ($race->isNationalOrInternational())
                    <p class="flex items-center gap-2">
                        <x-ri-target-line class="size-5 text-zinc-400 shrink-0" />
                        {{ $race->type->localizedName() }}
                    </p>
                @endif
            </div>
            <div class="mt-6  print:hidden">
                    
                <div class="hidden sm:block">
                    <nav class="-mb-0.5 flex space-x-8">

                        <x-tab-link href="{{ route('races.show', $race) }}" :active="request()->routeIs('races.show', $race)">{{ __('Summary') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.participants.index', $race) }}"  :active="request()->routeIs('races.participants.index', $race)">{{ __('Participants') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.tires', $race) }}"  :active="request()->routeIs('races.tires', $race)">{{ __('Tires') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.transponders', $race) }}"  :active="request()->routeIs('races.transponders', $race)">{{ __('Transponders') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.results.index', $race) }}"  :active="request()->routeIs('races.results.*') || request()->routeIs('results.show')">{{ __('Results') }}</x-tab-link>
                        
                    </nav>
                </div>
            </div>
        </div>
