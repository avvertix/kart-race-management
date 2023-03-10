@props(['disabled' => false])

<div class="relative">
    <input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'pl-8 border-zinc-300 focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50 rounded-md shadow-sm']) !!}>

    <div class="absolute top-0 left-0 flex items-center h-full p-2 text-zinc-600">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
        </svg>
    </div>
      
    <div wire:loading wire:target="search" class="absolute top-0 right-0 flex items-center h-full p-2 text-orange-50 bg-orange-600 rounded-r-md ">
        {{ __('Searching...') }}
    </div>
</div>
