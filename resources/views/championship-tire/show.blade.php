<x-app-layout>
    <x-slot name="title">
        {{ $tire->name }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">

        @section('actions')

            @can('update', $tire)
                <x-button-link href="{{ route('tire-options.edit', $tire) }}">
                    {{ __('Edit tire') }}
                </x-button-link>
            @endcan

        @endsection

        @include('championship.partials.heading')
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-4">

            <div class="p-4 bg-white shadow-xl rounded">
                <p>{{ $tire->name }}</p>
                <p class="text-2xl font-bold">
                    {{ $tire->formattedPrice() }}
                </p>
            </div>

            <div>
                <ul>
                    @foreach ($activities as $item)
                        <li><span class="inline-block px-2 py-1 text-sm rounded-full bg-white">{{ $item->event }}</span> <x-time :value="$item->created_at" /></li>
                    @endforeach
                </ul>
            </div>
            
        
        </div>
    </div>
</x-app-layout>
