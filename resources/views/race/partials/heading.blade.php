<div class="relative border-b-2 border-zinc-200 pb-5 sm:pb-0">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
                    {{ $race->title }}
                    <p class="text-base font-light">{{ $championship->title }}</p>
                </h2>
                <div class="mt-3 flex md:absolute md:top-3 md:right-0 md:mt-0 gap-2">

                    @can('create', \App\Model\Participant::class)
                        <x-button-link href="{{ route('races.participants.create', $race) }}">
                            {{ __('Add participant') }}
                        </x-button-link>
                    @endcan

                    @can('update', $race)
                        <x-button-link href="{{ route('races.export.participants', $race) }}">
                            {{ __('Export participants') }}
                        </x-button-link>
                    @endcan

                    @can('create', \App\Model\Transponder::class)
                        <x-button-link href="{{ route('races.export.transponders', $race) }}">
                            {{ __('Export transponders') }}
                        </x-button-link>
                    @endcan
                    
                    @can('update', $race)
                        <x-button-link href="{{ route('races.edit', $race) }}">
                            {{ __('Edit race') }}
                        </x-button-link>
                    @endcan
                </div>
            </div>
            <div class="mt-2 flex items-center text-sm text-zinc-500">
                <div>
                    <svg class="mr-1.5 h-5 w-5 flex-shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                    </svg>
                </div>
                {{ $race->period }}

                <div class="ml-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mr-1.5 h-5 w-5 flex-shrink-0 text-zinc-400">
                        <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                {{ $race->track }}

                @if ($race->type)
                    <div class="ml-4">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mr-1.5 h-5 w-5 flex-shrink-0 text-zinc-400">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-1.503.204A6.5 6.5 0 117.95 3.83L6.927 5.62a1.453 1.453 0 001.91 2.02l.175-.087a.5.5 0 01.224-.053h.146a.5.5 0 01.447.724l-.028.055a.4.4 0 01-.357.221h-.502a2.26 2.26 0 00-1.88 1.006l-.044.066a2.099 2.099 0 001.085 3.156.58.58 0 01.397.547v1.05a1.175 1.175 0 002.093.734l1.611-2.014c.192-.24.296-.536.296-.842 0-.316.128-.624.353-.85a1.363 1.363 0 00.173-1.716l-.464-.696a.369.369 0 01.527-.499l.343.257c.316.237.738.275 1.091.098a.586.586 0 01.677.11l1.297 1.297z" clip-rule="evenodd" />
                        </svg>                      
                    </div>
                    <span title="{{ $race->type?->description()}}">{{ $race->type?->localizedName() }}</span>
                @endif
            </div>
            <div class="mt-6">
                    
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
