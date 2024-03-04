<x-app-layout>
    <x-slot name="title">
        {{ $category->name }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">

        @section('actions')

            @can('update', $category)
                <x-button-link href="{{ route('categories.edit', $category) }}">
                    {{ __('Edit category') }}
                </x-button-link>
            @endcan

        @endsection

        @include('championship.partials.heading')
    </x-slot>


    <div class="pb-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-4">

            <div class="p-4 bg-white shadow-xl rounded space-y-2">
                <p>
                    <span class="inline-block px-2 py-1 text-sm rounded-full {{ $category->enabled ? 'bg-lime-100 text-lime-800' : 'bg-zinc-100 text-zinc-800' }}">
                        {{ $category->enabled ? __('active') : __('inactive') }}
                    </span>
                </p>
                <p>{{ $category->name }}</p>
                <p>{{ $category->short_name }}</p>
                <p>
                    @if ($category->tire)
                        <a href="{{ route('tire-options.show', $category->tire) }}" target="_blank">{{ $category->tire?->name ?? __('All tires') }}</a>
                    @else
                        {{ __('All tires') }}
                    @endif
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
