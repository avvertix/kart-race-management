<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChampionshipCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Category::class, 'category');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Championship $championship)
    {
        return view('category.index', [
            'championship' => $championship,
            'categories' => $championship->categories()->with('tire')->withCount('participants')->orderBy('name', 'ASC')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Championship $championship)
    {
        return view('category.create', [
            'championship' => $championship,
            'tires' => $championship->tires,
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
                Rule::unique((new Category())->getTable(), 'name')
                    ->where(function ($query) use ($championship) {
                        return $query->where('championship_id', $championship->getKey());
                    }),
            ],
            'short_name' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:1000',
            'enabled' => 'nullable|boolean',
            'tire' => [
                'nullable',
                'integer',
                Rule::exists((new ChampionshipTire())->getTable(), 'id')->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                })],
            'registration_price' => 'nullable|integer|min:0',
        ]);

        $category = $championship->categories()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'short_name' => $validated['short_name'] ?? null,
            'enabled' => $request->boolean('enabled') ?? false,
            'championship_tire_id' => $validated['tire'] ?? null,
            'registration_price' => $validated['registration_price'] ?? null,
        ]);

        return redirect()->route('championships.categories.index', $championship)
            ->with('flash.banner', __(':category created.', [
                'category' => $category->name,
            ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category->load('tire')->loadCount('participants');

        $rawActivities = $category->activities()
            // ->forEvent('updated')
            ->with('causer')
            ->latest()
            ->get();

        $tireIds = $rawActivities->flatMap(function ($activity) {
            $attrs = $activity->properties->get('attributes', []);
            $old = $activity->properties->get('old', []);

            return array_filter([
                $attrs['championship_tire_id'] ?? null,
                $old['championship_tire_id'] ?? null,
            ], fn ($v) => ! is_null($v));
        })->unique()->values();

        $tiresById = $tireIds->isNotEmpty()
            ? ChampionshipTire::whereIn('id', $tireIds)->pluck('name', 'id')
            : collect();

        $activities = $rawActivities->map(function ($activity) use ($tiresById) {
            $attrs = $activity->properties->get('attributes', []);
            $old = $activity->properties->get('old', []);

            $changes = collect($attrs)->map(function ($newValue, $field) use ($old, $tiresById) {
                return [
                    'field' => $this->categoryFieldLabel($field),
                    'old' => $this->formatCategoryFieldValue($field, $old[$field] ?? null, $tiresById),
                    'new' => $this->formatCategoryFieldValue($field, $newValue, $tiresById),
                ];
            })->values();

            return [
                'event' => $activity->event,
                'date' => $activity->created_at,
                'causer' => $activity->causer?->name,
                'changes' => $changes,
            ];
        });

        return view('category.show', [
            'category' => $category,
            'championship' => $category->championship,
            'activities' => $activities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('category.edit', [
            'category' => $category,
            'championship' => $category->championship,
            'tires' => $category->championship->tires,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $championship = $category->championship;

        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                'max:250',
                Rule::unique((new Category())->getTable(), 'name')
                    ->ignore($category)
                    ->where(function ($query) use ($championship) {
                        return $query->where('championship_id', $championship->getKey());
                    }),
            ],
            'short_name' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:1000',
            'enabled' => 'nullable|boolean',
            'tire' => [
                'nullable',
                'integer',
                Rule::exists((new ChampionshipTire())->getTable(), 'id')->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                })],
            'registration_price' => 'nullable|integer|min:0',
        ]);

        if ($request->has('registration_price') && $request->integer('registration_price') !== $category->registration_price) {
            $hasParticipants = Participant::where('championship_id', $championship->getKey())->where('category_id', $category->getKey())->exists();

            if ($hasParticipants) {
                throw ValidationException::withMessages(['registration_price' => __('The registration price cannot be changed because one or more competitors already registered in it.')]);
            }
        }

        $category->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'enabled' => $request->boolean('enabled') ?? false,
            'championship_tire_id' => $validated['tire'] ?? null,
            'registration_price' => $validated['registration_price'] ?? null,
        ]);

        return redirect()->route('championships.categories.index', $championship)
            ->with('flash.banner', __(':category updated.', [
                'category' => $category->name,
            ]));
    }

    private function categoryFieldLabel(string $field): string
    {
        return match ($field) {
            'code' => __('Code'),
            'name' => __('Name'),
            'description' => __('Description'),
            'enabled' => __('Status'),
            'short_name' => __('Short name'),
            'championship_tire_id' => __('Tire'),
            'registration_price' => __('Registration price'),
            default => $field,
        };
    }

    private function formatCategoryFieldValue(string $field, mixed $value, $tiresById): string
    {
        if (is_null($value)) {
            return '—';
        }

        return match ($field) {
            'enabled' => $value ? __('Active') : __('Inactive'),
            'championship_tire_id' => $tiresById->get($value) ?? __('Unknown tire (#:id)', ['id' => $value]),
            'registration_price' => number_format($value / 100, 2, ',', '.').' €',
            default => (string) $value,
        };
    }
}
