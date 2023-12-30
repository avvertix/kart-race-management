<x-app-layout>
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


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{ $category->name }}
            
        
        </div>
    </div>
</x-app-layout>
