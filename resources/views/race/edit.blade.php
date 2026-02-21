<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.show', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Edit :race', ['race' => $race->title]) }}</span>
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

<form method="POST" action="{{ route('races.update', $race) }}">
    @method('PUT')
    @csrf
        
            @include('race.partials.form')
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                
                <div></div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <x-button class="">
                                {{ __('Save race') }}
                            </x-button>
                        </div>
                </div>
            </div>

</form>

<x-section-border />

<form method="POST" action="{{ route('races.scoring.update', $race) }}">
    @method('PUT')
    @csrf

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Scoring') }}</x-slot>
        <x-slot name="description">{{ __('Configure scoring settings for this race.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="point_multiplier" value="{{ __('Point multiplier') }}" />
                        <p class="text-zinc-600 text-sm">{{ __('Set a multiplier for the championship points awarded in this race. Leave blank for default.') }}</p>
                        <x-input id="point_multiplier" type="number" name="point_multiplier" step="0.01" min="0" class="mt-1 block w-full" :value="old('point_multiplier', $race->point_multiplier)" />
                        <x-input-error for="point_multiplier" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="rain" class="flex items-center">
                            <x-checkbox id="rain" name="rain" value="1" :checked="old('rain', $race->rain)" />
                            <span class="ms-2 text-sm text-zinc-600">{{ __('Rain race') }}</span>
                        </x-label>
                        <p class="text-zinc-500 text-sm mt-1">{{ __('Mark this race as a rain race. This may affect championship point calculations.') }}</p>
                    </div>
                </div>
            </div>

            <div class="px-4 py-5">
                <x-button>
                    {{ __('Save scoring settings') }}
                </x-button>
            </div>
    </div>
</div>

</form>

<x-section-border />


<form method="POST" action="{{ route('races.destroy', $race) }}">
    @method('DELETE')
    @csrf
        
    
<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Cancel Race') }}</x-slot>
        <x-slot name="description">{{ __('Cancel the race.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">
        
            <div class="px-4 py-5">
                <div class="">
                    <x-danger-button class="" type="submit">
                        {{ __('Cancel race') }}
                    </x-danger-button>
                </div>
            </div>
    </div>
</div>
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                
                <div></div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            
                        </div>
                </div>
            </div>

</form>
        </div>
    </div>
</x-app-layout>
