<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Championships') }}
        </h2>
        @can('create', \App\Model\Championship::class)
            <div>
                <x-button-link href="{{ route('championships.create') }}">
                    {{ __('Create a championship') }}
                </x-button-link>
            </div>
        @endcan
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-zinc-300">
                    <thead class="bg-zinc-50">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-zinc-900 sm:pl-6">Title</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-zinc-900">Period</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">Edit</span>
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white">

                        @forelse ($championships as $item)

                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-zinc-900 sm:pl-6"><a href="{{ route('championships.show', $item->uuid) }}" class=" hover:text-orange-900">{{ $item->title }}</a></td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-zinc-500">{{ $item->period }}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    @can('update', $item)
                                        <a href="{{ route('championships.edit', $item->uuid) }}" class="text-orange-600 hover:text-orange-900">Edit<span class="sr-only">, {{ $item->title }}</span></a>
                                    @endcan
                                </td>
                            </tr>

                            
                        @empty
                            <div class="bg-orange-50 border border-orange-200 rounded-md p-2 flex">
                                <p>{{ __('No championships at the moment') }}</p>

                                @can('create', \App\Model\Championship::class)
                                    <p>
                                        <x-button-link href="{{ route('championships.create') }}">
                                            {{ __('Create a championship') }}
                                        </x-button-link>
                                    </p>
                                @endcan
                            </div>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
            </div>
                
            

            {{ $championships->links() }}
        
        </div>
    </div>
</x-app-layout>
