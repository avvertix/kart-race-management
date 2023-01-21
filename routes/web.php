<?php

use App\Http\Controllers\ChampionshipController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RaceImportController;
use App\Http\Controllers\RaceInChampionshipController;
use App\Http\Controllers\RaceParticipantController;
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

Route::get('/', function () {
    return view('welcome');
})->name('races.index');

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
        
        Route::resource('races.participants', RaceParticipantController::class)->shallow();

    });
