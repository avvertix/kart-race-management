<?php

declare(strict_types=1);

use App\Features\LicencesAndEnrollments;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Notifications;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', Profile::class)->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::livewire('settings/security', Security::class)
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');

    Route::livewire('settings/notifications', Notifications::class)
        ->middleware(EnsureFeaturesAreActive::using(LicencesAndEnrollments::class))
        ->name('notifications.edit');
});

if (Features::canManagePasskeys()) {
    Route::get('.well-known/passkey-endpoints', function () {
        return response()->json([
            'enroll' => route('security.edit'),
            'manage' => route('security.edit'),
        ]);
    })->name('well-known.passkeys');
}
