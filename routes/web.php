<?php

declare(strict_types=1);

use App\Features\LicencesAndProducts;
use App\Http\Controllers\BibReservationController;
use App\Http\Controllers\BibReservationImportController;
use App\Http\Controllers\BibReservationStatusController;
use App\Http\Controllers\ChampionshipAwardController;
use App\Http\Controllers\ChampionshipBannerController;
use App\Http\Controllers\ChampionshipBonusController;
use App\Http\Controllers\ChampionshipBonusImportController;
use App\Http\Controllers\ChampionshipCategoryController;
use App\Http\Controllers\ChampionshipController;
use App\Http\Controllers\ChampionshipDuplicateDriverController;
use App\Http\Controllers\ChampionshipEnrollmentController;
use App\Http\Controllers\ChampionshipParticipantController;
use App\Http\Controllers\ChampionshipPenaltyController;
use App\Http\Controllers\ChampionshipPenaltyImportController;
use App\Http\Controllers\ChampionshipPointSchemeController;
use App\Http\Controllers\ChampionshipPromoCodeController;
use App\Http\Controllers\ChampionshipTireController;
use App\Http\Controllers\CommunicationMessageController;
use App\Http\Controllers\ConfigureRacePenaltySheetController;
use App\Http\Controllers\ConfirmParticipantController;
use App\Http\Controllers\ConfirmParticipantPaymentController;
use App\Http\Controllers\CopyChampionshipBibReservationsController;
use App\Http\Controllers\CopyChampionshipCategoriesController;
use App\Http\Controllers\CopyChampionshipTiresController;
use App\Http\Controllers\EnrollmentReviewController;
use App\Http\Controllers\ExportChampionshipParticipantsController;
use App\Http\Controllers\ExportRaceHeatGridController;
use App\Http\Controllers\ExportRaceParticipantsController;
use App\Http\Controllers\ExportRaceParticipantsForSigningController;
use App\Http\Controllers\ExportRaceParticipantsForTimingController;
use App\Http\Controllers\ExportRacePrefinaleGridController;
use App\Http\Controllers\ExportRaceVerifiedDriversController;
use App\Http\Controllers\LicenceController;
use App\Http\Controllers\LicenceRequestController;
use App\Http\Controllers\LicenceRequestReviewController;
use App\Http\Controllers\ListRacesWithOpenRegistrationController;
use App\Http\Controllers\MyEnrollmentController;
use App\Http\Controllers\MyLicenceController;
use App\Http\Controllers\MyOrderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrbitsBackupController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ParticipantFederationAuthorizationController;
use App\Http\Controllers\ParticipantPaymentController;
use App\Http\Controllers\ParticipantSignatureNotificationController;
use App\Http\Controllers\ParticipantTiresController;
use App\Http\Controllers\ParticipantTireVerificationController;
use App\Http\Controllers\ParticipantTransponderController;
use App\Http\Controllers\PrintRaceParticipantReceiptsController;
use App\Http\Controllers\PrintRaceParticipantsController;
use App\Http\Controllers\PrintRacePenaltySheetController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductGrantController;
use App\Http\Controllers\PromoCodeClaimController;
use App\Http\Controllers\PublicChampionshipAwardController;
use App\Http\Controllers\PublicChampionshipEnrollmentController;
use App\Http\Controllers\PublicProductController;
use App\Http\Controllers\PublicPromoCodeController;
use App\Http\Controllers\PublicRaceResultController;
use App\Http\Controllers\RaceCombinedResultController;
use App\Http\Controllers\RaceCommunicationImportController;
use App\Http\Controllers\RaceCommunicationsController;
use App\Http\Controllers\RaceController;
use App\Http\Controllers\RaceHeatGridController;
use App\Http\Controllers\RaceImportController;
use App\Http\Controllers\RaceInChampionshipController;
use App\Http\Controllers\RaceParticipantController;
use App\Http\Controllers\RacePaymentsController;
use App\Http\Controllers\RaceRegistrationController;
use App\Http\Controllers\RaceTiresController;
use App\Http\Controllers\RaceTranspondersController;
use App\Http\Controllers\RaceTranspondersImportController;
use App\Http\Controllers\RecalculateRaceWildcardsController;
use App\Http\Controllers\ResultRaceController;
use App\Http\Controllers\SwitchLanguageController;
use App\Http\Controllers\UpdateChampionshipBibSettingsController;
use App\Http\Controllers\UpdateChampionshipBonusSettingsController;
use App\Http\Controllers\UpdateChampionshipEnrollmentSettingsController;
use App\Http\Controllers\UpdateChampionshipEntitlementSettingsController;
use App\Http\Controllers\UpdateChampionshipLicenceSettingsController;
use App\Http\Controllers\UpdateChampionshipPaymentSettingsController;
use App\Http\Controllers\UpdateChampionshipRegistrationFormController;
use App\Http\Controllers\UpdateRaceScoringController;
use App\Http\Controllers\UserController;
use App\Livewire\LicenceRequestForm;
use App\Livewire\LicenceRequestReview;
use App\Livewire\LinkedDrivers;
use Avvertix\AgentRequest\LaravelAgentRequest\Http\Middleware\DenyAgentMiddleware;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

Route::get('/', ListRacesWithOpenRegistrationController::class)->name('welcome');

Route::middleware([
    'auth',
    'auth.session',
    'verified',
    DenyAgentMiddleware::class,
])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::livewire('drivers', LinkedDrivers::class)->name('drivers.index');
});

// Championships and Races management

Route::middleware([
    'auth',
    'auth.session',
    'verified',
    DenyAgentMiddleware::class,
])
    ->prefix('m')
    ->group(function () {

        Route::resource('championships', ChampionshipController::class);

        Route::resource('races', RaceController::class)->only(['show', 'edit', 'update', 'destroy']);

        Route::put('races/{race}/scoring', UpdateRaceScoringController::class)->name('races.scoring.update');
        Route::post('races/{race}/wildcards/recalculate', RecalculateRaceWildcardsController::class)->name('races.wildcards.recalculate');

        Route::get('championships/{championship}/races/import', [RaceImportController::class, 'create'])->name('championships.races.import.create');

        Route::post('championships/{championship}/races/import', [RaceImportController::class, 'store'])->name('championships.races.import.store');

        Route::get('championships/{championship}/banner', [ChampionshipBannerController::class, 'index'])
            ->middleware('cache.headers:public;max_age=3600')
            ->name('championships.banner.index');

        Route::put('championships/{championship}/payment', UpdateChampionshipPaymentSettingsController::class)->name('championships.payment.update');

        Route::get('championships/{championship}/bonus-settings', [UpdateChampionshipBonusSettingsController::class, 'edit'])->name('championships.bonus-settings.edit');
        Route::put('championships/{championship}/bonus-settings', [UpdateChampionshipBonusSettingsController::class, 'update'])->name('championships.bonus-settings.update');

        Route::get('championships/{championship}/licence-settings', [UpdateChampionshipLicenceSettingsController::class, 'edit'])->name('championships.licence-settings.edit');
        Route::put('championships/{championship}/licence-settings', [UpdateChampionshipLicenceSettingsController::class, 'update'])->name('championships.licence-settings.update');

        Route::put('championships/{championship}/registration-form', UpdateChampionshipRegistrationFormController::class)->name('championships.registration-form.update');

        Route::put('championships/{championship}/bib-settings', UpdateChampionshipBibSettingsController::class)->name('championships.bib-settings.update');

        Route::post('championships/{championship}/banner', [ChampionshipBannerController::class, 'store'])->name('championships.banner.store');

        Route::delete('championships/{championship}/banner', [ChampionshipBannerController::class, 'destroy'])->name('championships.banner.destroy');

        Route::resource('championships.races', RaceInChampionshipController::class)->shallow()->only(['create', 'store']);

        Route::resource('championships.participants', ChampionshipParticipantController::class)->shallow()->only(['index']);

        Route::get('championships/{championship}/export-participants', ExportChampionshipParticipantsController::class)->name('championships.export.participants');

        Route::get('championships/{championship}/duplicate-drivers', [ChampionshipDuplicateDriverController::class, 'index'])->name('championships.duplicate-drivers.index');
        Route::post('championships/{championship}/duplicate-drivers', [ChampionshipDuplicateDriverController::class, 'store'])->name('championships.duplicate-drivers.store');

        Route::resource('championships.bonuses', ChampionshipBonusController::class)->shallow()->except(['destroy']);

        Route::get('championships/{championship}/bonuses/import', [ChampionshipBonusImportController::class, 'create'])->name('championships.bonuses.import.create');
        Route::post('championships/{championship}/bonuses/import', [ChampionshipBonusImportController::class, 'store'])->name('championships.bonuses.import.store');

        Route::get('championships/{championship}/categories/copy', [CopyChampionshipCategoriesController::class, 'create'])->name('championships.categories.copy');
        Route::post('championships/{championship}/categories/copy', [CopyChampionshipCategoriesController::class, 'store'])->name('championships.categories.store-copy');
        Route::resource('championships.categories', ChampionshipCategoryController::class)->shallow()->except(['destroy']);

        Route::get('championships/{championship}/tire-options/copy', [CopyChampionshipTiresController::class, 'create'])->name('championships.tire-options.copy');
        Route::post('championships/{championship}/tire-options/copy', [CopyChampionshipTiresController::class, 'store'])->name('championships.tire-options.store-copy');
        Route::resource('championships.tire-options', ChampionshipTireController::class)->shallow()->except(['destroy']);

        Route::resource('championships.point-schemes', ChampionshipPointSchemeController::class)->shallow()->except(['destroy', 'show']);

        Route::resource('championships.awards', ChampionshipAwardController::class)->shallow();

        Route::post('awards/{award}/toggle-publish', [ChampionshipAwardController::class, 'togglePublish'])->name('awards.toggle-publish');

        Route::get('awards/{award}/participants/{participant}/breakdown', [ChampionshipAwardController::class, 'breakdown'])->name('awards.participants.breakdown');

        Route::resource('championships.bib-reservations', BibReservationController::class)->shallow();

        Route::get('championships/{championship}/bib-reservations/import', [BibReservationImportController::class, 'create'])->name('championships.bib-reservations.import.create');
        Route::post('championships/{championship}/bib-reservations/import', [BibReservationImportController::class, 'store'])->name('championships.bib-reservations.import.store');

        Route::get('championships/{championship}/bib-reservations/copy', [CopyChampionshipBibReservationsController::class, 'create'])->name('championships.bib-reservations.copy');
        Route::post('championships/{championship}/bib-reservations/copy', [CopyChampionshipBibReservationsController::class, 'store'])->name('championships.bib-reservations.store-copy');

        Route::resource('races.results', ResultRaceController::class)->shallow()->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

        Route::get('races/{race}/heat-grids/export', ExportRacePrefinaleGridController::class)->name('races.export.prefinale-grid');

        Route::get('races/{race}/heat-grids/export/heats/{heat}', ExportRaceHeatGridController::class)->whereNumber('heat')->name('races.export.heat-grid');

        Route::post('races/{race}/results/combine-qualifying', [RaceCombinedResultController::class, 'qualifying'])->name('races.results.combine-qualifying');

        Route::post('races/{race}/results/combine-heats', [RaceCombinedResultController::class, 'heats'])->name('races.results.combine-heats');

        Route::get('races/{race}/heat-grids', [RaceHeatGridController::class, 'index'])->name('races.heat-grids.index');

        Route::post('results/{result}/toggle-publish', [ResultRaceController::class, 'togglePublish'])->name('results.toggle-publish');

        Route::post('results/{result}/link-participants', [ResultRaceController::class, 'linkParticipants'])->name('results.link-participants');

        Route::post('races/{race}/results/link-participants', [ResultRaceController::class, 'linkAllParticipants'])->name('races.results.link-participants');

        Route::post('races/{race}/results/publish-all', [ResultRaceController::class, 'publishAll'])->name('races.results.publish-all');

        Route::post('results/{result}/assign-points', [ResultRaceController::class, 'assignPoints'])->name('results.assign-points');

        Route::post('races/{race}/results/assign-points', [ResultRaceController::class, 'assignPointsToAll'])->name('races.results.assign-points');

        Route::resource('races.participants', RaceParticipantController::class)->shallow();

        Route::resource('orbits-backups', OrbitsBackupController::class)->except(['create', 'edit', 'update']);

        Route::get('races/{race}/participants/print', PrintRaceParticipantsController::class)->name('races.participants.print');

        Route::get('races/{race}/participant-receipts/print', PrintRaceParticipantReceiptsController::class)->name('races.participant-receipts.print');

        Route::get('races/{race}/penalty-sheet', ConfigureRacePenaltySheetController::class)->name('races.penalty-sheet.configure');

        Route::get('races/{race}/penalty-sheet/print', PrintRacePenaltySheetController::class)->name('races.penalty-sheet.print');

        Route::get('races/{race}/payments', RacePaymentsController::class)->name('races.payments');

        Route::post('participants/{participant}/confirm-payment', ConfirmParticipantPaymentController::class)->name('participants.confirm-payment');

        Route::get('races/{race}/tires', RaceTiresController::class)->name('races.tires');

        Route::resource('participants.tires', ParticipantTiresController::class)->shallow()->only(['index', 'create', 'store', 'edit', 'update']);

        Route::get('races/{race}/transponders', RaceTranspondersController::class)->name('races.transponders');

        Route::get('races/{race}/transponders/import', [RaceTranspondersImportController::class, 'create'])->name('races.transponders.import.create');
        Route::post('races/{race}/transponders/import', [RaceTranspondersImportController::class, 'store'])->name('races.transponders.import.store');

        Route::resource('participants.transponders', ParticipantTransponderController::class)->shallow()->except(['show']);

        Route::get('races/{race}/export-participants', ExportRaceParticipantsController::class)->name('races.export.participants');

        Route::get('races/{race}/export-transponders', ExportRaceParticipantsForTimingController::class)->name('races.export.transponders');

        Route::get('races/{race}/export-verified-drivers', ExportRaceVerifiedDriversController::class)->name('races.export.verified-drivers');

        Route::get('races/{race}/export-signature', ExportRaceParticipantsForSigningController::class)->name('races.export.signature');

        Route::resource('communications', CommunicationMessageController::class)->except(['create', 'show']);

        Route::resource('championships.penalties', ChampionshipPenaltyController::class)->shallow()->except(['show']);

        Route::get('championships/{championship}/penalties/import', [ChampionshipPenaltyImportController::class, 'create'])->name('championships.penalties.import.create');
        Route::post('championships/{championship}/penalties/import', [ChampionshipPenaltyImportController::class, 'store'])->name('championships.penalties.import.store');

        Route::get('races/{race}/communications', RaceCommunicationsController::class)->name('races.communications.index');

        Route::get('races/{race}/communications/import', [RaceCommunicationImportController::class, 'create'])->name('races.communications.import.create');
        Route::post('races/{race}/communications/import', [RaceCommunicationImportController::class, 'store'])->name('races.communications.import.store');

        Route::resource('users', UserController::class)->except(['show']);

        Route::middleware(EnsureFeaturesAreActive::using(LicencesAndProducts::class))->group(function () {
            Route::get('championships/{championship}/enrollment-settings', [UpdateChampionshipEnrollmentSettingsController::class, 'edit'])->name('championships.enrollment-settings.edit');
            Route::put('championships/{championship}/enrollment-settings', [UpdateChampionshipEnrollmentSettingsController::class, 'update'])->name('championships.enrollment-settings.update');
            Route::put('championships/{championship}/entitlement-settings', UpdateChampionshipEntitlementSettingsController::class)->name('championships.entitlement-settings.update');

            Route::get('championships/{championship}/promo-codes', [ChampionshipPromoCodeController::class, 'index'])->name('championships.promo-codes.index');
            Route::get('championships/{championship}/promo-codes/create', [ChampionshipPromoCodeController::class, 'create'])->name('championships.promo-codes.create');
            Route::post('championships/{championship}/promo-codes', [ChampionshipPromoCodeController::class, 'store'])->name('championships.promo-codes.store');
            Route::post('promo-codes/{promoCode}/revoke', [ChampionshipPromoCodeController::class, 'revoke'])->name('promo-codes.revoke');

            Route::post('bib-reservations/{bib_reservation}/confirm', [BibReservationStatusController::class, 'confirm'])->name('bib-reservations.confirm');
            Route::post('bib-reservations/{bib_reservation}/release', [BibReservationStatusController::class, 'release'])->name('bib-reservations.release');

            Route::get('championships/{championship}/enrollments', [ChampionshipEnrollmentController::class, 'index'])->name('championships.enrollments.index');
            Route::get('championships/{championship}/enrollments/create', [ChampionshipEnrollmentController::class, 'create'])->name('championships.enrollments.create');
            Route::post('championships/{championship}/enrollments', [ChampionshipEnrollmentController::class, 'store'])->name('championships.enrollments.store');
            Route::get('enrollments/{enrollment}', [EnrollmentReviewController::class, 'show'])->name('enrollments.show');
            Route::post('enrollments/{enrollment}/verify-licence', [EnrollmentReviewController::class, 'verifyLicence'])->name('enrollments.verify-licence');
            Route::post('enrollments/{enrollment}/confirm', [EnrollmentReviewController::class, 'confirm'])->name('enrollments.confirm');
            Route::post('enrollments/{enrollment}/reject', [EnrollmentReviewController::class, 'reject'])->name('enrollments.reject');
            Route::post('enrollments/{enrollment}/withdraw', [EnrollmentReviewController::class, 'withdraw'])->name('enrollments.withdraw');
            Route::put('enrollments/{enrollment}/race-number', [EnrollmentReviewController::class, 'raceNumber'])->name('enrollments.race-number.update');
            Route::put('enrollments/{enrollment}/competitor', [EnrollmentReviewController::class, 'competitor'])->name('enrollments.competitor.update');
            Route::post('enrollments/{enrollment}/payment/confirm', [EnrollmentReviewController::class, 'confirmPayment'])->name('enrollments.payment.confirm');
            Route::post('enrollments/{enrollment}/payment/reject', [EnrollmentReviewController::class, 'rejectPayment'])->name('enrollments.payment.reject');

            Route::resource('products', ProductController::class)->except(['show', 'destroy']);
            Route::post('products/{product}/grants', [ProductGrantController::class, 'store'])->name('products.grants.store');
            Route::delete('product-grants/{productGrant}', [ProductGrantController::class, 'destroy'])->name('product-grants.destroy');

            Route::resource('licences', LicenceController::class)->only(['index', 'show']);
            Route::post('licences/{licence}/verify', [LicenceController::class, 'verify'])->name('licences.verify');

            Route::get('licence-requests', [LicenceRequestReviewController::class, 'index'])->name('licence-request-reviews.index');
            Route::livewire('licence-requests/{licenceRequest}', LicenceRequestReview::class)->name('licence-request-reviews.show');

            Route::resource('orders', OrderController::class)->only(['index', 'show']);
            Route::put('orders/{order}/price', [OrderController::class, 'price'])->name('orders.price');
            Route::post('orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
            Route::post('orders/{order}/reject-payment', [OrderController::class, 'rejectPayment'])->name('orders.reject-payment');
        });

    });

// Products, open to everyone, and the licences and orders of the signed-in user, while in early access (see App\Features\LicencesAndProducts)

Route::get('championships/{championship}/enrollment', [PublicChampionshipEnrollmentController::class, 'show'])
    ->middleware(EnsureFeaturesAreActive::using(LicencesAndProducts::class))
    ->name('public.championships.enrollment');

Route::get('promo-codes/{promoCode}', [PublicPromoCodeController::class, 'show'])
    ->middleware(EnsureFeaturesAreActive::using(LicencesAndProducts::class))
    ->name('public.promo-codes.show');

Route::get('products', [PublicProductController::class, 'index'])
    ->middleware(EnsureFeaturesAreActive::using(LicencesAndProducts::class))
    ->name('public.products.index');

Route::get('products/{product}', [PublicProductController::class, 'show'])
    ->middleware(EnsureFeaturesAreActive::using(LicencesAndProducts::class))
    ->name('public.products.show');

Route::get('products/{product}/register', [PublicProductController::class, 'register'])
    ->middleware(['guest', EnsureFeaturesAreActive::using(LicencesAndProducts::class)])
    ->name('public.products.register');

Route::middleware([
    'auth',
    'auth.session',
    'verified',
    DenyAgentMiddleware::class,
    EnsureFeaturesAreActive::using(LicencesAndProducts::class),
])->group(function () {
    Route::get('products/{product}/continue', [PublicProductController::class, 'continue'])->name('public.products.continue');

    Route::post('products/{product}/order', [PublicProductController::class, 'order'])->name('public.products.order');

    Route::post('championships/{championship}/enrollment', [PublicChampionshipEnrollmentController::class, 'store'])->name('public.championships.enrollment.store');
    Route::get('my-enrollments/{enrollment}', [MyEnrollmentController::class, 'show'])->name('my-enrollments.show');
    Route::post('my-enrollments/{enrollment}/withdraw', [MyEnrollmentController::class, 'withdraw'])->name('my-enrollments.withdraw');
    Route::put('my-enrollments/{enrollment}/licence', [MyEnrollmentController::class, 'updateLicence'])->name('my-enrollments.licence.update');
    Route::put('my-enrollments/{enrollment}/competitor', [MyEnrollmentController::class, 'updateCompetitor'])->name('my-enrollments.competitor.update');
    Route::post('my-enrollments/{enrollment}/payment', [MyEnrollmentController::class, 'pay'])
        ->middleware('throttle:10,10')
        ->name('my-enrollments.payment.store');

    Route::post('promo-codes/find', [PromoCodeClaimController::class, 'find'])
        ->middleware('throttle:20,1')
        ->name('promo-codes.find');
    Route::post('promo-codes/{promoCode}/claim', [PromoCodeClaimController::class, 'claim'])
        ->middleware('throttle:10,1')
        ->name('promo-codes.claim');
    Route::get('my-enrollments/{enrollment}/race-number', [MyEnrollmentController::class, 'editRaceNumber'])->name('my-enrollments.race-number.edit');
    Route::put('my-enrollments/{enrollment}/race-number', [MyEnrollmentController::class, 'updateRaceNumber'])
        ->middleware('throttle:30,1')
        ->name('my-enrollments.race-number.update');

    Route::get('orders/{order}', [MyOrderController::class, 'show'])->name('my-orders.show');

    Route::resource('licences', MyLicenceController::class)->names('my-licences');

    Route::post('licence-requests', [LicenceRequestController::class, 'store'])->name('licence-requests.store');
    Route::get('licence-requests/{licenceRequest}', [LicenceRequestController::class, 'show'])->name('licence-requests.show');
    Route::post('licence-requests/{licenceRequest}/payment', [LicenceRequestController::class, 'pay'])
        ->middleware('throttle:10,10')
        ->name('licence-requests.payment.store');
    Route::livewire('licence-requests/{licenceRequest}/edit', LicenceRequestForm::class)->name('licence-requests.edit');
    Route::delete('licence-requests/{licenceRequest}', [LicenceRequestController::class, 'destroy'])->name('licence-requests.destroy');
    Route::get('licence-requests/{licenceRequest}/documents/{document}', [LicenceRequestController::class, 'document'])->name('licence-requests.documents.show');
    Route::get('licence-requests/{licenceRequest}/forms/{document}', [LicenceRequestController::class, 'form'])->name('licence-requests.forms.show');

    Route::get('licences/{licence}/temporary-licence', [MyLicenceController::class, 'temporaryLicence'])
        ->middleware('signed')
        ->name('my-licences.temporary-licence');

    Route::post('orders/{order}/payment', [MyOrderController::class, 'pay'])
        ->middleware('throttle:10,10')
        ->name('my-orders.payment.store');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
});

// Public race results

Route::get('races/{race}/results', [PublicRaceResultController::class, 'index'])->name('public.races.results.index');

Route::get('results/{result}/show', [PublicRaceResultController::class, 'show'])->name('public.results.show');

// Public championship awards

Route::get('championships/{championship}/awards', [PublicChampionshipAwardController::class, 'index'])->name('public.championships.awards.index');

Route::get('awards/{award}', [PublicChampionshipAwardController::class, 'show'])->name('public.awards.show');

// Self registration

Route::middleware(DenyAgentMiddleware::class)->resource('races.registration', RaceRegistrationController::class)->only(['show', 'create', 'store'])->shallow();

Route::post('registration-verification', [ParticipantSignatureNotificationController::class, 'store'])
    ->name('registration-verification.send')
    ->middleware(['signed', 'throttle:60,1']);

Route::post('payment-verification', [ParticipantPaymentController::class, 'store'])
    ->name('payment-verification.store')
    ->middleware(['signed', 'throttle:60,1']);

Route::get('payment-verification/{payment}', [ParticipantPaymentController::class, 'show'])
    ->name('payment-verification.show')
    ->middleware(['signed']);

Route::post('federation-authorization', [ParticipantFederationAuthorizationController::class, 'store'])
    ->name('federation-authorization.store')
    ->middleware(['signed', 'throttle:60,1']);

Route::get('federation-authorization/{authorization}', [ParticipantFederationAuthorizationController::class, 'show'])
    ->name('federation-authorization.show')
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

require __DIR__.'/settings.php';
