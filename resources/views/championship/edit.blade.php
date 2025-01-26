<x-app-layout>
    <x-slot name="title">
        {{ $championship->title }} - {{ __('Edit championship') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Edit :championship', ['championship' => $championship->title]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

        <x-validation-errors class="mb-4" />

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Championship details') }}</x-slot>
                <x-slot name="description">
                    
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">

                <form method="POST" action="{{ route('championships.update', $championship) }}">
                    @method('PUT')
                    @csrf

                    @include('championship.partials.form')

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <x-section-border />
        
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Banner') }}</x-slot>
                <x-slot name="description">
                    {{ __('The championship banner image') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">

                <div>
                
                    @if ($championship->banner_path)
                        <img src="{{ route('championships.banner.index', $championship) }}" >

                        <form method="POST" action="{{ route('championships.banner.destroy', $championship) }}">
                            @method('DELETE')
                            @csrf

                            <div class="flex items-center justify-end mt-4">
                                <x-danger-button type="submit" class="ml-4">
                                    {{ __('Remove banner') }}
                                </x-danger-button>
                            </div>
                        </form>

                        <x-section-border />
                    @endif
                
                </div>

                <form method="POST" enctype="multipart/form-data" action="{{ route('championships.banner.store', $championship) }}">
                    @csrf

                    <div>
                        <x-label for="banner" value="{{ __('Banner image') }}" />
                        <p class="text-zinc-600 text-sm">{{ __('Image file (jpg, png). Maximum 1200x600 pixels or 10 MB.') }}</p>
                        <x-input-error for="banner" />
                        <x-input id="banner" class="block mt-1 w-full" type="file" name="banner" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        <x-section-border />

        <livewire:wildcard-settings :championship="$championship" /> 
        
        
        </div>
    </div>
</x-app-layout>
