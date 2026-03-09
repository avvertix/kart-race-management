<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\AwardResource;
use App\Models\Championship;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChampionshipAwardApiController extends Controller
{
    /**
     * Return a JSON list of awards for the given championship with their public URLs.
     */
    public function __invoke(Championship $championship): AnonymousResourceCollection
    {
        $awards = $championship->awards()->orderBy('name')->get();

        return AwardResource::collection($awards);
    }
}
