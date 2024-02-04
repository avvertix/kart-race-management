<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'categories' => $championship->categories()->with('tire')->orderBy('name', 'ASC')->get(),
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
                    })
            ],
            'short_name' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:1000',
            'enabled' => 'nullable|boolean',
            'tire' => [
                'nullable',
                'integer',
                Rule::exists((new ChampionshipTire())->getTable(), 'id')->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                })]
        ]);

        $category = $championship->categories()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'short_name' => $validated['short_name'] ?? null,
            'enabled' => $request->boolean('enabled') ?? false,
            'championship_tire_id' => $validated['tire'] ?? null,
        ]);

        return redirect()->route('championships.categories.index', $championship)
            ->with('flash.banner', __(':category created.', [
                'category' => $category->name
            ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        $category->load('tire');
        
        return view('category.show', [
            'category' => $category,
            'championship' => $category->championship,
            'activities' => $category->activities,
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
                    })
            ],
            'short_name' => 'nullable|string|max:250',
            'description' => 'nullable|string|max:1000',
            'enabled' => 'nullable|boolean',
            'tire' => [
                'nullable',
                'integer',
                Rule::exists((new ChampionshipTire())->getTable(), 'id')->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                })]
        ]);

        $category->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'enabled' => $request->boolean('enabled') ?? false,
            'championship_tire_id' => $validated['tire'] ?? null,
        ]);

        return redirect()->route('championships.categories.index', $championship)
            ->with('flash.banner', __(':category updated.', [
                'category' => $category->name
            ]));
    }
}
