<?php

declare(strict_types=1);

use App\Http\Controllers\BibReservationController;
use App\Http\Controllers\ChampionshipBannerController;
use App\Http\Controllers\ChampionshipBonusController;
use App\Http\Controllers\ChampionshipCategoryController;
use App\Http\Controllers\ChampionshipController;
use App\Http\Controllers\ChampionshipParticipantController;
use App\Http\Controllers\ChampionshipTireController;
use App\Http\Controllers\CommunicationMessageController;
use App\Http\Controllers\ConfirmParticipantController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportChampionshipParticipantsController;
use App\Http\Controllers\ExportRaceParticipantsController;
use App\Http\Controllers\ExportRaceParticipantsForAciPromotionController;
use App\Http\Controllers\ExportRaceParticipantsForSigningController;
use App\Http\Controllers\ExportRaceParticipantsForTimingController;
use App\Http\Controllers\ListRacesWithOpenRegistrationController;
use App\Http\Controllers\OrbitsBackupController;
use App\Http\Controllers\ParticipantPaymentController;
use App\Http\Controllers\ParticipantSignatureNotificationController;
use App\Http\Controllers\ParticipantTiresController;
use App\Http\Controllers\ParticipantTireVerificationController;
use App\Http\Controllers\ParticipantTransponderController;
use App\Http\Controllers\PrintRaceParticipantReceiptsController;
use App\Http\Controllers\PrintRaceParticipantsController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RaceImportController;
use App\Http\Controllers\RaceInChampionshipController;
use App\Http\Controllers\RaceParticipantController;
use App\Http\Controllers\RaceRegistrationController;
use App\Http\Controllers\RaceTiresController;
use App\Http\Controllers\RaceTranspondersController;
use App\Http\Controllers\SwitchLanguageController;
use App\Http\Controllers\UpdateChampionshipBonusSettingsController;
use App\Http\Controllers\UpdateChampionshipPaymentSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', ListRacesWithOpenRegistrationController::class)->name('welcome');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

// Championships and Races management

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])
    ->prefix('m')
    ->group(function () {

        Route::resource('championships', ChampionshipController::class);

        Route::resource('races', RaceController::class)->only(['show', 'edit', 'update', 'destroy']);

        Route::get('championships/{championship}/races/import', [RaceImportController::class, 'create'])->name('championships.races.import.create');

        Route::post('championships/{championship}/races/import', [RaceImportController::class, 'store'])->name('championships.races.import.store');

        Route::get('championships/{championship}/banner', [ChampionshipBannerController::class, 'index'])
            ->middleware('cache.headers:public;max_age=3600')
            ->name('championships.banner.index');

        Route::put('championships/{championship}/payment', UpdateChampionshipPaymentSettingsController::class)->name('championships.payment.update');

        Route::put('championships/{championship}/bonus-settings', UpdateChampionshipBonusSettingsController::class)->name('championships.bonus-settings.update');

        Route::post('championships/{championship}/banner', [ChampionshipBannerController::class, 'store'])->name('championships.banner.store');

        Route::delete('championships/{championship}/banner', [ChampionshipBannerController::class, 'destroy'])->name('championships.banner.destroy');

        Route::resource('championships.races', RaceInChampionshipController::class)->shallow()->only(['create', 'store']);

        Route::resource('championships.participants', ChampionshipParticipantController::class)->shallow()->only(['index']);

        Route::get('championships/{championship}/export-participants', ExportChampionshipParticipantsController::class)->name('championships.export.participants');

        Route::resource('championships.bonuses', ChampionshipBonusController::class)->shallow()->except(['destroy']);

        Route::resource('championships.categories', ChampionshipCategoryController::class)->shallow()->except(['destroy']);

        Route::resource('championships.tire-options', ChampionshipTireController::class)->shallow()->except(['destroy']);

        Route::resource('championships.bib-reservations', BibReservationController::class)->shallow();

        Route::resource('races.participants', RaceParticipantController::class)->shallow();

        Route::resource('orbits-backups', OrbitsBackupController::class)->except(['create', 'edit', 'update']);

        Route::get('races/{race}/participants/print', PrintRaceParticipantsController::class)->name('races.participants.print');

        Route::get('races/{race}/participant-receipts/print', PrintRaceParticipantReceiptsController::class)->name('races.participant-receipts.print');

        Route::get('races/{race}/tires', RaceTiresController::class)->name('races.tires');

        Route::resource('participants.tires', ParticipantTiresController::class)->shallow()->only(['index', 'create', 'store', 'edit', 'update']);

        Route::get('races/{race}/transponders', RaceTranspondersController::class)->name('races.transponders');

        Route::resource('participants.transponders', ParticipantTransponderController::class)->shallow()->except(['show']);

        Route::get('races/{race}/export-participants', ExportRaceParticipantsController::class)->name('races.export.participants');

        Route::get('races/{race}/export-transponders', ExportRaceParticipantsForTimingController::class)->name('races.export.transponders');

        Route::get('races/{race}/export-aci', ExportRaceParticipantsForAciPromotionController::class)->name('races.export.aci');

        Route::get('races/{race}/export-signature', ExportRaceParticipantsForSigningController::class)->name('races.export.signature');

        Route::resource('communications', CommunicationMessageController::class)->except(['create', 'show']);
    });

// Self registration

Route::resource('races.registration', RaceRegistrationController::class)->only(['show', 'create', 'store'])->shallow();

Route::post('registration-verification', [ParticipantSignatureNotificationController::class, 'store'])
    ->name('registration-verification.send')
    ->middleware(['signed', 'throttle:3,10']);

Route::post('payment-verification', [ParticipantPaymentController::class, 'store'])
    ->name('payment-verification.store')
    ->middleware(['signed', 'throttle:3,10']);

Route::get('payment-verification/{payment}', [ParticipantPaymentController::class, 'show'])
    ->name('payment-verification.show')
    ->middleware(['signed']);

// Signature for registration

Route::get('confirm-participation', ConfirmParticipantController::class)
    ->name('participant.sign.create')
    ->middleware('signed');

Route::get('tires-verification/{registration}', [ParticipantTireVerificationController::class, 'show'])
    ->name('tires-verification.show');

// Privacy Policy page

Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('policy.show');

Route::get('/swtich-language', SwitchLanguageController::class)->name('language.change');
