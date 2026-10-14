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

                    <flux:dropdown position="bottom" align="end">
                        <x-button type="button">
                            {{ __('Export or print') }}

                            <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </x-button>

                        <flux:menu class="md:w-96">
                            @can('update', $race)
                                <x-menu-item :href="route('races.export.participants', $race)" :description="__('Export all registered participants')">
                                    <x-slot:icon><x-ri-file-excel-line /></x-slot:icon>
                                    {{ __('Export participants') }}
                                </x-menu-item>
                                <x-menu-item :href="route('races.export.signature', $race)" :description="__('Export form for signing the attendence of the briefing')">
                                    <x-slot:icon><x-ri-sketching /></x-slot:icon>
                                    {{ __('Export briefing signature module') }}
                                </x-menu-item>

                                <flux:menu.separator />

                                <x-menu-item :href="route('races.participants.print', $race)" :description="__('Print all participants registrations')">
                                    <x-slot:icon><x-ri-printer-line /></x-slot:icon>
                                    {{ __('Print participants') }}
                                </x-menu-item>
                                <x-menu-item :href="route('races.participant-receipts.print', $race)" target="_blank" :description="__('Print receipts for all participants')">
                                    <x-slot:icon><x-ri-receipt-line /></x-slot:icon>
                                    {{ __('Print receipts') }}
                                </x-menu-item>
                            @endcan

                            @can('create', \App\Model\Transponder::class)
                                @can('update', $race)
                                    <flux:menu.separator />
                                @endcan

                                @if ($race->isNationalOrInternational())
                                    <x-menu-item :href="route('races.export.aci', $race)" :description="__('Export confirmed participants as requested by ACI Karting')">
                                        <x-slot:icon><x-ri-table-2 /></x-slot:icon>
                                        {{ __('Export for ACI Italian Cup') }}
                                    </x-menu-item>

                                    <flux:menu.separator />
                                @endif

                                <x-menu-item :href="route('races.export.transponders', $race)" :description="__('Export drivers and transponder for MyLaps Orbits')">
                                    <x-slot:icon><x-ri-steering-2-line /></x-slot:icon>
                                    {{ __('Export transponders') }}
                                </x-menu-item>
                                <x-menu-item :href="route('races.penalty-sheet.configure', $race)" :description="__('Print weight and pre-grid check sheet')">
                                    <x-slot:icon><x-ri-scales-3-line /></x-slot:icon>
                                    {{ __('Print check sheet') }}
                                </x-menu-item>
                            @endcan
                        </flux:menu>
                    </flux:dropdown>

                    
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
                        
                        @can('update', $race)
                            <x-tab-link href="{{ route('races.payments', $race) }}"  :active="request()->routeIs('races.payments', $race)">{{ __('Payments') }}</x-tab-link>
                        @endcan

                        <x-tab-link href="{{ route('races.tires', $race) }}"  :active="request()->routeIs('races.tires', $race)">{{ __('Tires') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.transponders', $race) }}"  :active="request()->routeIs('races.transponders', $race)">{{ __('Transponders') }}</x-tab-link>
                        
                        <x-tab-link href="{{ route('races.results.index', $race) }}"  :active="request()->routeIs('races.results.*') || request()->routeIs('results.show')">{{ __('Results') }}</x-tab-link>

                        @can('viewAny', \App\Models\RaceCommunication::class)
                            <x-tab-link href="{{ route('races.communications.index', $race) }}" :active="request()->routeIs('races.communications.index', $race)">{{ __('Communications') }}</x-tab-link>
                        @endcan
                        
                    </nav>
                </div>
            </div>
        </div>
