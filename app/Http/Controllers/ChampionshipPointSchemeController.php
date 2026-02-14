<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipPointScheme;
use App\Models\ResultStatus;
use App\Models\RunType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChampionshipPointSchemeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ChampionshipPointScheme::class, 'point_scheme');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Championship $championship)
    {
        return view('point-scheme.index', [
            'championship' => $championship,
            'pointSchemes' => $championship->pointSchemes()->orderBy('name', 'ASC')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Championship $championship)
    {
        return view('point-scheme.create', [
            'championship' => $championship,
            'runTypes' => [RunType::QUALIFY, RunType::RACE_1, RunType::RACE_2],
            'resultStatuses' => [
                ResultStatus::DID_NOT_START,
                ResultStatus::DID_NOT_FINISH,
                ResultStatus::DISQUALIFIED,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Championship $championship)
    {
        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:250',
                Rule::unique((new ChampionshipPointScheme)->getTable(), 'name')
                    ->where(function ($query) use ($championship) {
                        return $query->where('championship_id', $championship->getKey());
                    }),
            ],
            'points_config' => 'required|array',
            'points_config.*.positions' => 'nullable|array',
            'points_config.*.positions.*' => 'numeric|min:0',
            'points_config.*.statuses' => 'nullable|array',
            'points_config.*.statuses.*.mode' => 'required|in:fixed,ranked',
            'points_config.*.statuses.*.points' => 'nullable|numeric|min:0',
        ]);

        $pointScheme = $championship->pointSchemes()->create([
            'name' => $validated['name'],
            'points_config' => $validated['points_config'],
        ]);

        return redirect()->route('championships.point-schemes.index', $championship)
            ->with('flash.banner', __(':name created.', [
                'name' => $pointScheme->name,
            ]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChampionshipPointScheme $pointScheme)
    {
        return view('point-scheme.edit', [
            'pointScheme' => $pointScheme,
            'championship' => $pointScheme->championship,
            'runTypes' => [RunType::QUALIFY, RunType::RACE_1, RunType::RACE_2],
            'resultStatuses' => [
                ResultStatus::DID_NOT_START,
                ResultStatus::DID_NOT_FINISH,
                ResultStatus::DISQUALIFIED,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChampionshipPointScheme $pointScheme)
    {
        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:250',
                Rule::unique((new ChampionshipPointScheme)->getTable(), 'name')
                    ->ignore($pointScheme)
                    ->where(function ($query) use ($pointScheme) {
                        return $query->where('championship_id', $pointScheme->championship_id);
                    }),
            ],
            'points_config' => 'required|array',
            'points_config.*.positions' => 'nullable|array',
            'points_config.*.positions.*' => 'numeric|min:0',
            'points_config.*.statuses' => 'nullable|array',
            'points_config.*.statuses.*.mode' => 'required|in:fixed,ranked',
            'points_config.*.statuses.*.points' => 'nullable|numeric|min:0',
        ]);

        $pointScheme->update([
            'name' => $validated['name'],
            'points_config' => $validated['points_config'],
        ]);

        return redirect()->route('championships.point-schemes.index', $pointScheme->championship)
            ->with('flash.banner', __(':name updated.', [
                'name' => $pointScheme->name,
            ]));
    }
}
