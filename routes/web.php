<?php

use App\Http\Controllers\ChampionshipController;
use App\Http\Controllers\ChampionshipParticipantController;
use App\Http\Controllers\ConfirmParticipantController;
use App\Http\Controllers\ExportRaceParticipantsController;
use App\Http\Controllers\ExportRaceParticipantsForTimingController;
use App\Http\Controllers\ListRacesWithOpenRegistrationController;
use App\Http\Controllers\ParticipantPaymentController;
use App\Http\Controllers\ParticipantSignatureNotificationController;
use App\Http\Controllers\ParticipantTiresController;
use App\Http\Controllers\ParticipantTransponderController;
use App\Http\Controllers\PrintRaceParticipantsController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RaceImportController;
use App\Http\Controllers\RaceInChampionshipController;
use App\Http\Controllers\RaceParticipantController;
use App\Http\Controllers\RaceRegistrationController;
use App\Http\Controllers\RaceTiresController;
use App\Http\Controllers\RaceTranspondersController;
use App\Http\Controllers\SaveParticipantSignatureController;
use App\Http\Controllers\ShowParticipantSignatureFormController;
use App\Http\Controllers\SwitchLanguageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', ListRacesWithOpenRegistrationController::class)->name('welcome');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// Championships and Races management

Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified'
    ])
    ->prefix('m')
    ->group(function () {

        Route::resource('championships', ChampionshipController::class);

        Route::resource('races', RaceController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);

        Route::get('championships/{championship}/races/import', [RaceImportController::class, 'create'])->name('championships.races.import.create');
        
        Route::post('championships/{championship}/races/import', [RaceImportController::class, 'store'])->name('championships.races.import.store');
        
        Route::resource('championships.races', RaceInChampionshipController::class)->shallow()->only(['index', 'create', 'store']);
        
        Route::resource('championships.participants', ChampionshipParticipantController::class)->shallow()->only(['index']);
        
        Route::resource('races.participants', RaceParticipantController::class)->shallow();
        
        Route::get('races/{race}/participants/print', PrintRaceParticipantsController::class)->name('races.participants.print');
        
        Route::get('races/{race}/tires', RaceTiresController::class)->name('races.tires');
        
        Route::resource('participants.tires', ParticipantTiresController::class)->shallow()->only(['index', 'create', 'store', 'edit', 'update']);
        
        Route::get('races/{race}/transponders', RaceTranspondersController::class)->name('races.transponders');
        
        Route::resource('participants.transponders', ParticipantTransponderController::class)->shallow()->except(['destroy', 'show']);
        
        Route::get('races/{race}/export-participants', ExportRaceParticipantsController::class)->name('races.export.participants');
        
        Route::get('races/{race}/export-transponders', ExportRaceParticipantsForTimingController::class)->name('races.export.transponders');

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


// Privacy Policy page

Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('policy.show');

Route::get('/swtich-language', SwitchLanguageController::class)->name('language.change');
