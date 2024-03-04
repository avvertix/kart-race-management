<x-app-layout>
    <x-slot name="title">
        {{ __('Modify reservation for :bib', ['bib' => $reservation->bib]) }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">

        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.bib-reservations.index', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Modify reservation for :bib', ['bib' => $reservation->bib]) }}</span>
        </h2>
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('bib-reservations.update', $reservation) }}">
                @method('PUT')
                @csrf
                
                @include('bib-reservation.partials.form')
                
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    
                    <div></div>
    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        
                            <div class="px-4 py-5">
                                <x-button class="">
                                    {{ __('Update reservation') }}
                                </x-button>
                            </div>
                    </div>
                </div>
                
            </form>        
        
        </div>
    </div>
    
    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('bib-reservations.destroy', $reservation) }}">
                @method('DELETE')
                @csrf
                
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <x-section-title>
                        <x-slot name="title">{{ __('Delete Reservation') }}</x-slot>
                        <x-slot name="description">{{ __('Remove the reservation. Any participant can use the race number previously reserved.') }} </x-slot>
                    </x-section-title>

                    <div class="mt-5 md:mt-0 md:col-span-2">
        
                        <div class="px-4 py-5">
                            <x-danger-button type="submit" class="">
                                {{ __('Delete reservation') }}
                            </x-danger-button>

                        </div>

                    </div>
                </div>
                
            </form>        
        
        </div>
    </div>
</x-app-layout>
