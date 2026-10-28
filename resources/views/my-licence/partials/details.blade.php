@php
    $holder = $licence->holder;
@endphp

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-md border border-zinc-200 bg-white p-4">
        <h3 class="font-semibold text-zinc-800 mb-2">{{ __('Licence') }}</h3>
        <dl class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
            <dt class="text-zinc-500">{{ __('Number') }}</dt>
            <dd class="col-span-2 font-mono">{{ $licence->number }}</dd>
            <dt class="text-zinc-500">{{ __('Provider') }}</dt>
            <dd class="col-span-2">{{ $licence->provider->label() }}</dd>
            <dt class="text-zinc-500">{{ __('Licence type') }}</dt>
            <dd class="col-span-2">{{ $licence->licenceType?->name ?? $licence->role->label() }}</dd>
            @if ($licence->level)
                <dt class="text-zinc-500">{{ __('Level') }}</dt>
                <dd class="col-span-2">{{ $licence->level->label() }}</dd>
            @endif
            <dt class="text-zinc-500">{{ __('Grade') }}</dt>
            <dd class="col-span-2">{{ $licence->gradeName() }}</dd>
            <dt class="text-zinc-500">{{ __('Year') }}</dt>
            <dd class="col-span-2">{{ $licence->validity_year }}</dd>
            <dt class="text-zinc-500">{{ __('Status') }}</dt>
            <dd class="col-span-2">@include('my-licence.partials.status')</dd>
        </dl>
    </section>

    <section class="rounded-md border border-zinc-200 bg-white p-4">
        <h3 class="font-semibold text-zinc-800 mb-2">{{ __('Holder') }}</h3>
        <dl class="grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
            <dt class="text-zinc-500">{{ __('Name') }}</dt>
            <dd class="col-span-2">{{ $holder->fullName() }}</dd>
            <dt class="text-zinc-500">{{ __('Sex') }}</dt>
            <dd class="col-span-2">{{ $holder->sex->localizedName() }}</dd>
            <dt class="text-zinc-500">{{ __('Birth') }}</dt>
            <dd class="col-span-2">{{ $holder->birth_date }} &middot; {{ $holder->birth_place }}</dd>
            <dt class="text-zinc-500">{{ __('Nationality') }}</dt>
            <dd class="col-span-2">{{ $holder->nationality }}</dd>
            @if ($holder->fiscal_code)
                <dt class="text-zinc-500">{{ __('Fiscal code') }}</dt>
                <dd class="col-span-2 font-mono">{{ $holder->fiscal_code }}</dd>
            @endif
            <dt class="text-zinc-500">{{ __('E-Mail') }}</dt>
            <dd class="col-span-2">{{ $holder->email }}</dd>
            <dt class="text-zinc-500">{{ __('Phone number') }}</dt>
            <dd class="col-span-2">{{ $holder->phone }}</dd>
            <dt class="text-zinc-500">{{ __('Residence address') }}</dt>
            <dd class="col-span-2">{{ $holder->residence_address }}</dd>
        </dl>
    </section>
</div>
