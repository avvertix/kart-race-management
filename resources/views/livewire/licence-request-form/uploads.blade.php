{{-- The documents or forms to upload: $items are RequiredDocument, $isForm adds the pre-filled download --}}
@php
    $parents = $licenceRequest->parentHolders();
@endphp

@if ($items->isEmpty())
    <p class="text-sm text-zinc-600">{{ __('Nothing to upload.') }}</p>
@endif

<ul class="divide-y divide-zinc-200 rounded-md border border-zinc-200">
    @foreach ($items as $required)
        @php
            $key = $required->key();
            $upload = $licenceRequest->uploadFor($required);
            $canReplace = $licenceRequest->canReplaceUpload($upload);
            $isFlagged = $upload?->status === \App\Enums\LicenceRequestDocumentStatus::Flagged;
        @endphp
        <li wire:key="upload-{{ $key }}" @class(['p-3 space-y-2', 'bg-red-50' => $isFlagged])>
            <div class="flex flex-wrap items-start gap-2">
                <div class="flex-1 min-w-48">
                    <p class="font-medium text-zinc-900">
                        {{ $required->document->label() }}
                        @if ($required->parentIndex !== null)
                            <span class="font-normal text-zinc-600">&middot; {{ ($parents[$required->parentIndex] ?? null)?->fullName() ?? __('Parent or guardian :number', ['number' => $required->parentIndex + 1]) }}</span>
                        @endif
                    </p>
                    @if ($required->parentIndex === null && $required->document->description())
                        <p class="text-sm text-zinc-600">{{ $required->document->description() }}</p>
                    @endif
                    @if ($upload)
                        <p class="text-sm text-zinc-600 break-all">
                            {{ $upload->original_name }} &middot; {{ $upload->status->label() }}
                            &middot; <a href="{{ route('licence-requests.documents.show', [$licenceRequest, $upload]) }}" target="_blank" class="text-orange-600 hover:text-orange-900">{{ __('Open') }}</a>
                        </p>
                        @if ($upload->note)
                            <p @class(['text-sm', 'text-red-700' => $isFlagged, 'text-zinc-600' => ! $isFlagged])><span class="font-semibold">{{ __('Organizer') }}:</span> {{ $upload->note }}</p>
                        @endif
                    @else
                        <p class="text-sm text-zinc-500">{{ __('Not uploaded yet') }}</p>
                    @endif
                </div>

                @if ($isForm)
                    <a href="{{ route('licence-requests.forms.show', ['licenceRequest' => $licenceRequest, 'document' => $required->document]) }}" target="_blank" class="text-sm text-orange-600 hover:text-orange-900 py-1">{{ __('Download pre-filled') }}</a>
                @endif
            </div>

            @if ($canReplace)
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <label class="inline-flex cursor-pointer items-center rounded-md border border-zinc-300 bg-white px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50 focus-within:ring-2 focus-within:ring-orange-300" wire:loading.class="pointer-events-none opacity-60" wire:target="files.{{ $key }}">
                        <input type="file" wire:model="files.{{ $key }}" accept="image/png,image/jpeg,application/pdf" class="sr-only" aria-label="{{ __('File for :document', ['document' => $required->document->label()]) }}">
                        {{ $upload ? __('Replace file') : __('Choose file') }}
                    </label>
                    <span wire:loading wire:target="files.{{ $key }}" class="text-zinc-600" role="status">{{ __('Uploading…') }}</span>
                </div>
                <x-input-error for="files.{{ $key }}" />
            @endif
        </li>
    @endforeach
</ul>

<p class="text-sm text-zinc-500">{{ __('PDF, JPG or PNG, up to 10 MB each.') }}</p>
