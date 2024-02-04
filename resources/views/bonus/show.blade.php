<x-app-layout>
    <x-slot name="header">

        @section('actions')

            @can('update', $bonus)
                <x-button-link href="{{ route('categories.edit', $bonus) }}">
                    {{ __('Edit bonus') }}
                </x-button-link>
            @endcan

        @endsection

        @include('championship.partials.heading')
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-4">

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
    </div>
</x-app-layout>
