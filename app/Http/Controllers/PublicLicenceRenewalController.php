<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\LicenceTypeData;
use App\Enums\LicenceDocument;
use App\Enums\LicenceProvider;
use App\Models\LicenceRequest;
use App\Support\LicenceCatalogue;
use Illuminate\View\View;

/**
 * The public page explaining how licences are renewed on the instance: the Licence Types it handles, the price, the
 * documents to prepare and the steps, with the way in for guests and signed-in Users.
 */
class PublicLicenceRenewalController extends Controller
{
    public function __invoke(): View
    {
        $providers = LicenceCatalogue::providers();

        abort_if($providers === [], 404);

        return view('public-licence-renewal.show', [
            'renewalYear' => LicenceRequest::validityYearFor(now()),
            'renewalPrice' => LicenceCatalogue::renewalPriceLabel(),
            'licenceTypes' => collect($providers)->mapWithKeys(fn (LicenceProvider $provider) => [
                $provider->label() => LicenceCatalogue::types($provider)->map(fn (LicenceTypeData $type) => $type->name)->values(),
            ]),
            'documents' => collect(LicenceDocument::cases())
                ->map(fn (LicenceDocument $document) => [
                    'document' => $document,
                    'askedBy' => array_values(array_filter($providers, fn (LicenceProvider $provider) => $document->isAskedBy($provider))),
                ])
                ->filter(fn (array $item) => $item['askedBy'] !== [])
                ->map(fn (array $item) => [
                    ...$item,
                    'onlyFor' => count($item['askedBy']) < count($providers)
                        ? collect($item['askedBy'])->map(fn (LicenceProvider $provider) => $provider->label())->join(', ')
                        : null,
                ])
                ->values(),
        ]);
    }
}
