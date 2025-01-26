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
