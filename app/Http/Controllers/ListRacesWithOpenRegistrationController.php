<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Features\LicencesAndEnrollments;
use App\Models\Championship;
use App\Models\LicenceRequest;
use App\Models\Race;
use App\Support\LicenceCatalogue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class ListRacesWithOpenRegistrationController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $races = Race::query()
            ->withRegistrationOpen()
            ->visible()
            ->orWhere(function (Builder $query) {
                $query->active();
            })
            ->orWhere(function (Builder $query) {
                $query->where('event_start_at', '<=', now()->subDays(2))
                    ->where('event_start_at', '>=', now());
            })
            ->orderBy('event_start_at')
            ->with('championship')
            ->get();

        $hasLicencesAndEnrollments = Feature::active(LicencesAndEnrollments::class);

        return view('welcome', [
            'races' => $races,
            'openChampionships' => $hasLicencesAndEnrollments
                ? Championship::openForEnrollment()->load(['enrollmentOptions' => fn ($query) => $query->offered()])
                : collect(),
            'offersLicenceRenewal' => $hasLicencesAndEnrollments && LicenceCatalogue::providers() !== [],
            'renewalPrice' => $hasLicencesAndEnrollments ? LicenceCatalogue::renewalPriceLabel() : null,
            'renewalYear' => LicenceRequest::validityYearFor(now()),
        ]);
    }
}
