<x-app-layout>
    <x-slot name="title">
        {{ __('Communications') }}
    </x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Communications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    

        <div class="p-4 bg-white rounded">

            <h3 class="mb-4">{{ __('Create a communication') }}</h3>

            <form action="{{ route("communications.store") }}" method="POST" class="flex flex-col gap-3">
                @csrf

                @include('communication.partials.form')

                <div class="mt-4">
                    <x-button class="button-dark truncate text-center block" type="submit">
                        {{ __('Create message') }}
                    </x-button>
                </div>
            </form>

        </div>

        <div class="flex flex-col gap-8 mt-6">


            <table class="w-full text-sm">
                <thead class="">
                    <tr class="">
                        <td layout="border-b" class="w-4/12 text-xs">{{ __('Preview') }}</td>
                        <td layout="border-b" class="w-2/12 text-xs">{{ __('Status') }}</td>
                        <td layout="border-b" class="w-2/12 text-xs">{{ __('Target users') }}</td>
                        <td layout="border-b" class="w-1/12 text-xs">{{ __('Start date') }}</td>
                        <td layout="border-b" class="w-1/12 text-xs">{{ __('End date') }}</td>
                        <td layout="border-b" class="w-2/12 text-xs"></td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($communications as $communication)
                        <tr class="">
                            <td class="px-2 py-3 border-b">
                                {{ $communication }}
                            </td>
                            <td class="px-2 py-3 border-b">
                                {{ $communication->status }}
                            </td>
                            <td class="px-2 py-3 border-b">
                                {{ $communication->target_user_role?->join(', ') }}
                            </td>
                            <td class="px-2 py-3 border-b">
                                {{ $communication->starts_at }}
                            </td>
                            <td class="px-2 py-3 border-b">
                                {{ $communication->ends_at }}
                            </td>
                            <td class="px-2 py-3 border-b">
                                <div class="inline-flex gap-2">
                                    @can('update', $communication)
                                        <a class="underline" href="{{ route("communications.edit", $communication) }}">{{ __('Edit') }}</a>
                                    @endcan
                                    
                                    @can('delete', $communication)
                                        <form action="{{ route("communications.destroy", $communication) }}" method="post">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="underline cursor-pointer">{{ __('Delete') }}</button>
                                        </form>
                                    @endcan
                                </div>
                                
                            </td>
                        
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <p class="text-zinc-600 p-4">{{ __('No communication messages. Write one!') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            
        </div>
    </div>
</div>
</x-app-layout>