<div>
    <x-label for="start" value="{{ __('Start date') }}*" />
    <x-input id="start" class="block mt-1 w-full" type="date" pattern="\d{4}-\d{2}-\d{2}" name="start" :value="old('start', optional($championship ?? null)?->start_at?->toDateString())" required autofocus />
</div>

<div>
    <x-label for="end" value="{{ __('End date') }}" />
    <x-input id="end" class="block mt-1 w-full" type="date" pattern="\d{4}-\d{2}-\d{2}" name="end" :value="old('end', optional($championship ?? null)?->end_at?->toDateString())" />
</div>

<div class="mt-4">
    <x-label for="title" value="{{ __('Title') }}" />
    <x-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', optional($championship ?? null)->title)" />
</div>

<div class="mt-4">
    <x-label for="description" value="{{ __('Description') }}" />
    <x-input id="description" class="block mt-1 w-full" type="text" name="description" :value="old('description', optional($championship ?? null)->description)" />
</div>