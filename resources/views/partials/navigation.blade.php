{{--
    Main navigation items, shared between the desktop navbar and the mobile sidebar.
    $component: 'navbar' or 'sidebar'
--}}
@php
    $item = 'flux::'.$component.'.item';
@endphp

@auth
    @can('drivers:view')
        <x-dynamic-component :component="$item" :href="route('dashboard')" :current="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
        </x-dynamic-component>

        <x-dynamic-component :component="$item" :href="route('drivers.index')" :current="request()->routeIs('drivers.*', 'registration.*')">
            {{ __('Drivers and competitors') }}
        </x-dynamic-component>
    @endcan

    @can('create', \App\Models\Licence::class)
        <x-dynamic-component :component="$item" :href="route('my-licences.index')" :current="request()->routeIs('my-licences.*')">
            {{ __('My licences') }}
        </x-dynamic-component>
    @endcan

    @can('viewAny', \App\Models\Championship::class)
        <x-dynamic-component :component="$item" :href="route('championships.index')" :current="request()->routeIs('championships.*', 'categories.*', 'races.*', 'participants.*')">
            {{ __('Championships') }}
        </x-dynamic-component>
    @endcan

    @can('viewAny', \App\Models\Licence::class)
        <x-dynamic-component :component="$item" :href="route('licences.index')" :current="request()->routeIs('licences.*')">
            {{ __('All licences') }}
        </x-dynamic-component>
    @endcan

    @can('viewAny', \App\Models\Product::class)
        <x-dynamic-component :component="$item" :href="route('products.index')" :current="request()->routeIs('products.*')">
            {{ __('Products') }}
        </x-dynamic-component>
    @endcan

    @can('viewAny', \App\Models\Order::class)
        <x-dynamic-component :component="$item" :href="route('orders.index')" :current="request()->routeIs('orders.*')">
            {{ __('Orders') }}
        </x-dynamic-component>
    @endcan

    @can('viewAny', \App\Models\CommunicationMessage::class)
        <x-dynamic-component :component="$item" :href="route('communications.index')" :current="request()->routeIs('communications.*')">
            {{ __('Communications') }}
        </x-dynamic-component>
    @endcan

    @can('viewAny', \App\Models\OrbitsBackup::class)
        <x-dynamic-component :component="$item" :href="route('orbits-backups.index')" :current="request()->routeIs('orbits-backups.*')">
            {{ __('Backups') }}
        </x-dynamic-component>
    @endcan

    @can('viewAny', \App\Models\User::class)
        <x-dynamic-component :component="$item" :href="route('users.index')" :current="request()->routeIs('users.*')">
            {{ __('Users') }}
        </x-dynamic-component>
    @endcan
@endauth
