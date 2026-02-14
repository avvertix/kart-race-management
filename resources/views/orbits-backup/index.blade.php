<x-app-layout>
    <x-slot name="title">
        {{ __('Orbits Backup') }}
    </x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Orbits Backup') }}
        </h2>
    </x-slot>

    <div class="pb-12">
        <div class="px-4 sm:px-6 lg:px-8">
    

        <div class="p-4 bg-white rounded">

            <h3 class="mb-4">{{ __('Upload a backup') }}</h3>

            <form action="{{ route("orbits-backups.store") }}" enctype="multipart/form-data" method="POST" class="flex flex-col gap-3">
                @csrf

                @include('orbits-backup.partials.form')

                <div class="mt-4">
                    <x-button class="button-dark truncate text-center block" type="submit">
                        {{ __('Upload backup') }}
                    </x-button>
                </div>
            </form>

        </div>

        <div class="flex flex-col gap-8 mt-6">


            <table class="w-full text-sm">
                <thead class="">
                    <tr class="">
                        <td layout="border-b" class="w-5/12 text-xs">{{ __('Filename') }}</td>
                        <td layout="border-b" class="w-2/12 text-xs">{{ __('Creation date') }}</td>
                        <td layout="border-b" class="w-3/12 text-xs">{{ __('Championship') }}</td>
                        <td layout="border-b" class="w-2/12 text-xs"></td>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr class="">
                            <td class="px-2 py-3 border-b">
                                {{ $backup->filename }}
                            </td>
                            <td class="px-2 py-3 border-b">
                                <x-time :value="$backup->created_at" />
                            </td>
                            <td class="px-2 py-3 border-b">
                                {{ $backup->championship?->title }}
                            </td>
                            <td class="px-2 py-3 border-b">
                                <div class="inline-flex gap-2">
                                    @can('view', $backup)
                                        <a class="underline" href="{{ route("orbits-backups.show", $backup) }}">{{ __('Download') }}</a>
                                    @endcan
                                    
                                    @can('delete', $backup)
                                        <form action="{{ route("orbits-backups.destroy", $backup) }}" method="post">
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
                            <td colspan="4">
                                <p class="text-zinc-600 p-4">{{ __('No backups.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $backups->links() }}
            
        </div>
    </div>
</div>
</x-app-layout>