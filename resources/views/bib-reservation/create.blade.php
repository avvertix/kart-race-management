<x-app-layout>
    <x-slot name="title">
        {{ __('Reserve a race number') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">

        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.bib-reservations.index', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Reserve a race number') }}</span>
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('championships.bib-reservations.store', $championship) }}">
                @csrf
                
                @include('bib-reservation.partials.form')
                
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    
                    <div></div>
    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        
                            <div class="px-4 py-5">
                                <x-button class="">
                                    {{ __('Reserve number') }}
                                </x-button>
                            </div>
                    </div>
                </div>
                
            </form>        
        
        </div>
    </div>
</x-app-layout>
