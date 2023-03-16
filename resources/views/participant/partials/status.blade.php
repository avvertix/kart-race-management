@if ($item->registration_completed_at)
    <span class="px-2 py-1 rounded bg-blue-100 text-blue-800">{{ __('Registration completed') }}</span>
@elseif ($item->signatures_count !== null && $item->signatures_count == 0)
    <span class="px-2 py-1 rounded bg-red-100 text-red-800">{{ __('Signature Missing') }}</span>
@elseif ($item->confirmed_at)
    <span class="px-2 py-1 rounded bg-green-100 text-green-800">{{ __('Confirmed') }}</span>
@endif