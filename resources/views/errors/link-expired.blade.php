<x-app-layout>
    <x-slot name="header">
        <div class="relative pb-5 sm:pb-0 print:hidden">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-4xl text-zinc-800 leading-tight">
                    {{ __('Link Expired') }}
                </h2>
                <div class="mt-3 flex md:absolute md:top-3 md:right-0 md:mt-0 gap-2">

                </div>
            </div>
            <div class="prose prose-zinc">
                <p class="font-bold">{{ __('In case you received this link via email you can request a new one by clicking on the "View the participation" link at the bottom of the received email.') }}</p>

            </div>
            
        </div>

    </x-slot>


</x-app-layout>
