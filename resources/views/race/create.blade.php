<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.show', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Add new race') }}</span>
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

<form method="POST" action="{{ route('championships.races.store', $championship) }}">
@csrf
        
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Event period') }}</x-slot>
                    <x-slot name="description">
                        {{ __('When the race takes place.') }}
                        {{ __('For single day event specify only the "start date".') }}
                    </x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="start" value="{{ __('Start date') }}*" />
                                    <x-input id="start" type="date" name="start" class="mt-1 block w-full" required autofocus pattern="\d{4}-\d{2}-\d{2}" />
                                    <x-input-error for="start" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="end" value="{{ __('End Date') }}" />
                                    <x-input id="end" type="date" name="end" class="mt-1 block w-full" pattern="\d{4}-\d{2}-\d{2}" />
                                    <x-input-error for="end" class="mt-2" />
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <x-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Details') }}</x-slot>
                    <x-slot name="description">{{ __('The race details, like title and description.') }}</x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="title" value="{{ __('Title') }}*" />
                                    <x-input id="title" type="text" name="title" class="mt-1 block w-full" required autocomplete="title" />
                                    <x-input-error for="title" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="description" value="{{ __('Description') }}" />
                                    <x-input id="description" type="text" name="description" class="mt-1 block w-full" autocomplete="description" />
                                    <x-input-error for="description" class="mt-2" />
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="participants_total_limit" value="{{ __('Maximum number of participants (leave blank for no limit)') }}" />
                                    <x-input id="participants_total_limit" type="text" name="participants_total_limit" class="mt-1 block w-full" autocomplete="participants_total_limit" />
                                    <x-input-error for="participants_total_limit" class="mt-2" />
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <x-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Track') }}</x-slot>
                    <x-slot name="description">{{ __('The race track where the race takes place.') }}</x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="track" value="{{ __('Track') }}*" />
                                    <x-input id="track" type="text" name="track" class="mt-1 block w-full" required autocomplete="track" />
                                    <x-input-error for="track" class="mt-2" />
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <x-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Hide race') }}</x-slot>
                    <x-slot name="description">{{ __('Hide the race from public listing.') }}</x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                    <div class="px-4 py-5">
                        <div class="grid grid-cols-6 gap-6">
                            <div class="col-span-6 sm:col-span-4">
                                <label for="hidden" class="flex items-center">
                                    <x-checkbox id="hidden" name="hidden" value="true" />
                                    <span class="ml-2">{{ __('Hide the race from public listing') }}</span>
                                    <x-input-error for="hidden" class="mt-2" />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                
                <div></div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <x-button class="">
                                {{ __('Create race') }}
                            </x-button>
                        </div>
                </div>
            </div>

</form>
        </div>
    </div>
</x-app-layout>
