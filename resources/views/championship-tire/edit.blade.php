<x-app-layout>
    <x-slot name="header">

        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.tire-options.index', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Modify :tire', ['tire' => $tire->name]) }}</span>
        </h2>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('tire-options.update', $tire) }}">
                @method('PUT')
                @csrf
                
                @include('championship-tire.partials.form')
                
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    
                    <div></div>
    
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        
                            <div class="px-4 py-5">
                                <x-button class="">
                                    {{ __('Save tire') }}
                                </x-button>
                            </div>
                    </div>
                </div>
                
            </form>        
        
        </div>
    </div>
</x-app-layout>
