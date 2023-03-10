<div class="md:col-span-1 flex justify-between">
    <div class="px-4 sm:px-0">
        <div class="  md:sticky md:top-0">

            <h3 class=" text-lg font-medium text-zinc-900">{{ $title }}</h3>
            
            <p class=" mt-1 text-sm text-zinc-600">
                {{ $description }}
            </p>
        </div>
    </div>

    <div class="px-4 sm:px-0">
        {{ $aside ?? '' }}
    </div>
</div>
