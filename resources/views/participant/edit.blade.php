<x-app-layout>
    <x-slot name="title">
        {{ $participant->full_name }} - {{ __('Edit participant') }} - {{ $participant->race->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('races.show', $participant->race) }}">{{ $participant->race->title }}</a></span>
            <span>/</span>
            <span>{{ __('Edit participant') }}</span>
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            

            <form method="POST" action="{{ route('participants.update', $participant) }}">
                @method('PUT')
                @csrf
                    
                @include('participant.partials.form')
                
                @include('participant.partials.bonus')
                
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    
                    <div></div>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        
                            <div class="px-4 py-5">
                                <x-button class="">
                                    {{ __('Update participant') }}
                                </x-button>
                            </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>
