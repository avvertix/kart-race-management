<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('drivers:view', fn (User $user) => $user->hasPermission('drivers:view'));
        Gate::define('drivers:create', fn (User $user) => $user->hasPermission('drivers:create'));
        Gate::define('drivers:update', fn (User $user) => $user->hasPermission('drivers:update'));
        Gate::define('drivers:delete', fn (User $user) => $user->hasPermission('drivers:delete'));
    }
}
