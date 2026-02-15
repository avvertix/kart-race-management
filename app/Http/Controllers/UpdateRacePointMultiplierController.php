<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;

class UpdateRacePointMultiplierController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $validated = $this->validate($request, [
            'point_multiplier' => ['nullable', 'numeric', 'min:0'],
        ]);

        $race->update([
            'point_multiplier' => $validated['point_multiplier'] ?? null,
        ]);

        return to_route('races.edit', $race)
            ->with('flash.banner', __(':race point multiplier updated.', [
                'race' => $race->title,
            ]));
    }
}
