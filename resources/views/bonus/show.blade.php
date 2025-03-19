<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{$bonus->driver}} - {{ __('Bonus') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="actions">
        @can('update', $bonus)
            <x-button-link href="{{ route('bonuses.edit', $bonus) }}">
                {{ __('Edit bonus') }}
            </x-button-link>
        @endcan
    </x-slot>

        <div class="grid md:grid-cols-2 gap-4">

            <div class="p-4 bg-white shadow-xl rounded space-y-2">
                <p>{{ $bonus->driver }}</p>
                <p class="font-mono">{{ $bonus->driver_licence }}</p>
                <p>
                    <span class="font-bold">{{ $bonus->amount }}</span> {{ $bonus->bonus_type->localizedName() }}
                </p>
            </div>
            
            <div>
                <ul>
                    @foreach ($bonusUsage as $item)
                        
                    @endforeach
                </ul>
            </div>
        
        </div>

</x-championship-page-layout>
