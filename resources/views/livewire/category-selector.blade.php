<div class="flex flex-wrap flex-shrink-0 gap-2">
    @foreach ($categories as $key => $item)
        <label class="relative flex grow cursor-pointer rounded-lg border bg-white p-4 shadow-sm focus:outline-none focus:ring-2 focus-within:ring-2 focus-within:ring-orange-300 focus:ring-orange-300">
            <input type="radio" name="{{ $name }}" value="{{ $key }}" @checked($key === $value) class="peer sr-only" aria-labelledby="{{ $name }}-{{ $key }}-label" aria-describedby="{{ $name }}-{{ $key }}-description">
            
            <span class="flex flex-1">
                <span class="flex flex-col">
                    <span id="{{ $name }}-{{ $key }}-label" class="block text-sm font-medium text-zinc-900">{{ $item->name }}</span>
                    @if ($item instanceof \App\Support\Describable)
                        <span id="{{ $name }}-{{ $key }}-description" class="mt-1 flex items-center text-sm text-zinc-500">{{ $item->description() }}</span>
                    @endif
                </span>
            </span>

            <svg class="h-5 w-5 text-zinc-500 peer-checked:text-orange-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>

            <span class="pointer-events-none absolute -inset-px rounded-lg border-2 peer-checked:border-orange-500" aria-hidden="true"></span>
        </label>
    @endforeach
</div>
