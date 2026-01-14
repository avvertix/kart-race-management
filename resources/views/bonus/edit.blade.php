<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Edit bonus for participant :name', ['name' => $bonus->driver]) }} - {{ $championship->title }}
    </x-slot>

    <form method="POST" action="{{ route('bonuses.update', $bonus) }}">
        @method('PUT')
        @csrf
        
        @include('bonus.partials.form')
        
        <div class="md:grid md:grid-cols-3 md:gap-6">
            
            <div></div>

            <div class="mt-5 md:mt-0 md:col-span-2">
                
                    <div class="px-4 py-5">
                        <x-button class="">
                            {{ __('Save bonus') }}
                        </x-button>
                    </div>
            </div>
        </div>
        
    </form>
</x-championship-page-layout>
