{{-- The details of a holder or parent: $person is HolderData --}}
<dl class="grid grid-cols-3 gap-x-4 gap-y-1 text-sm">
    <dt class="text-zinc-500">{{ __('Name') }}</dt>
    <dd class="col-span-2">{{ $person->fullName() }} &middot; {{ $person->sex->localizedName() }}</dd>
    <dt class="text-zinc-500">{{ __('Birth') }}</dt>
    <dd class="col-span-2">{{ $person->birth_date }} &middot; {{ $person->birth_place }}</dd>
    <dt class="text-zinc-500">{{ __('Nationality') }}</dt>
    <dd class="col-span-2">{{ $person->nationality }}</dd>
    @if ($person->fiscal_code)
        <dt class="text-zinc-500">{{ __('Fiscal code') }}</dt>
        <dd class="col-span-2 font-mono break-all">{{ $person->fiscal_code }}</dd>
    @endif
    <dt class="text-zinc-500">{{ __('E-Mail') }}</dt>
    <dd class="col-span-2 break-all">{{ $person->email }}</dd>
    <dt class="text-zinc-500">{{ __('Phone number') }}</dt>
    <dd class="col-span-2">{{ $person->phone }}</dd>
    <dt class="text-zinc-500">{{ __('Residence address') }}</dt>
    <dd class="col-span-2">{{ $person->residence_address }}</dd>
</dl>
