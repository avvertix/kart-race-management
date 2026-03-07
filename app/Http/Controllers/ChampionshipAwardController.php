<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CalculateAwardRanking;
use App\Models\AwardRankingMode;
use App\Models\AwardType;
use App\Models\Championship;
use App\Models\ChampionshipAward;
use App\Models\WildcardFilter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChampionshipAwardController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ChampionshipAward::class, 'award');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Championship $championship)
    {
        return view('award.index', [
            'championship' => $championship,
            'awards' => $championship->awards()->with('category')->orderBy('name', 'ASC')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, Championship $championship)
    {
        $type = AwardType::tryFrom($request->query('type', 'category')) ?? AwardType::Category;

        return view('award.create', [
            'championship' => $championship,
            'type' => $type,
            'categories' => $championship->categories()->where('enabled', true)->orderBy('name')->get(),
            'races' => $championship->races()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Championship $championship)
    {
        $validated = $this->validate($request, $this->validationRules());

        $award = $championship->awards()->create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'ranking_mode' => $validated['ranking_mode'] ?? AwardRankingMode::All->value,
            'best_n' => $validated['best_n'] ?? null,
            'wildcard_filter' => $validated['wildcard_filter'] ?? WildcardFilter::All->value,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        if ($validated['type'] === AwardType::Overall->value && ! empty($validated['category_ids'])) {
            $award->categories()->sync($validated['category_ids']);
        }

        if (($validated['ranking_mode'] ?? null) === AwardRankingMode::SpecificRaces->value && ! empty($validated['race_ids'])) {
            $award->races()->sync($validated['race_ids']);
        }

        return redirect()->route('championships.awards.index', $championship)
            ->with('flash.banner', __('Award created.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ChampionshipAward $award)
    {
        $ranking = app(CalculateAwardRanking::class)($award);
        $championship = $award->championship;

        return view('award.show', [
            'championship' => $championship,
            'award' => $award->load(['category', 'categories', 'races']),
            'ranking' => $ranking,
            'races' => $championship->races()->orderBy('event_start_at')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChampionshipAward $award)
    {
        $championship = $award->championship;

        return view('award.edit', [
            'championship' => $championship,
            'award' => $award->load(['categories', 'races']),
            'type' => $award->type,
            'categories' => $championship->categories()->where('enabled', true)->orderBy('name')->get(),
            'races' => $championship->races()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChampionshipAward $award)
    {
        $validated = $this->validate($request, $this->validationRules());

        $award->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'ranking_mode' => $validated['ranking_mode'] ?? AwardRankingMode::All->value,
            'best_n' => $validated['best_n'] ?? null,
            'wildcard_filter' => $validated['wildcard_filter'] ?? WildcardFilter::All->value,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        if ($validated['type'] === AwardType::Overall->value) {
            $award->categories()->sync($validated['category_ids'] ?? []);
        }

        if (($validated['ranking_mode'] ?? null) === AwardRankingMode::SpecificRaces->value) {
            $award->races()->sync($validated['race_ids'] ?? []);
        } else {
            $award->races()->detach();
        }

        return redirect()->route('championships.awards.index', $award->championship)
            ->with('flash.banner', __('Award updated.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChampionshipAward $award)
    {
        $championship = $award->championship;

        $award->delete();

        return redirect()->route('championships.awards.index', $championship)
            ->with('flash.banner', __('Award deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(): array
    {
        return [
            'type' => ['required', Rule::in(array_column(AwardType::cases(), 'value'))],
            'name' => ['required', 'string', 'max:250'],
            'category_id' => ['required_if:type,category', 'nullable', 'exists:categories,id'],
            'ranking_mode' => ['required_if:type,category', 'nullable', Rule::in(array_column(AwardRankingMode::cases(), 'value'))],
            'best_n' => ['required_if:ranking_mode,best_n', 'nullable', 'integer', 'min:1'],
            'wildcard_filter' => ['nullable', Rule::in(array_column(WildcardFilter::cases(), 'value'))],
            'race_ids' => ['required_if:ranking_mode,specific', 'nullable', 'array'],
            'race_ids.*' => ['exists:races,id'],
            'category_ids' => ['required_if:type,overall', 'nullable', 'array', 'min:1'],
            'category_ids.*' => ['exists:categories,id'],
        ];
    }
}
