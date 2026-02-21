<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;

class UpdateRaceScoringController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $validated = $this->validate($request, [
            'point_multiplier' => ['nullable', 'numeric', 'min:0'],
            'rain' => ['nullable', 'boolean'],
        ]);

        $race->update([
            'point_multiplier' => $validated['point_multiplier'] ?? null,
            'rain' => $request->boolean('rain'),
        ]);

        return to_route('races.edit', $race)
            ->with('flash.banner', __(':race scoring settings updated.', [
                'race' => $race->title,
            ]));
    }
}
