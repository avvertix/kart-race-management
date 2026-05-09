<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;

class ConfigureRacePenaltySheetController extends Controller
{
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $race->load('championship.categories');

        $confirmedCategoryIds = $race->participants()
            ->confirmed()
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->unique();

        $categories = $race->championship->categories
            ->whereIn('id', $confirmedCategoryIds)
            ->values();

        return view('race.penalty-sheet-configure', [
            'race' => $race,
            'championship' => $race->championship,
            'categories' => $categories,
        ]);
    }
}
