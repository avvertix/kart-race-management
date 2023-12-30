<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Championship;
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
            'categories' => $championship->categories()->orderBy('name', 'ASC')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Championship $championship)
    {
        return view('category.create', [
            'championship' => $championship,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Championship $championship)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:250|unique:' . Category::class .',name',
            'description' => 'nullable|string|max:1000',
            'enabled' => 'nullable|boolean',
        ]);

        $category = $championship->categories()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'enabled' => $request->boolean('enabled') ?? false,
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
        return view('category.show', [
            'category' => $category,
            'championship' => $category->championship,
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
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $this->validate($request, [
            'name' => ['required','string','max:250', Rule::unique((new Category())->getTable(), 'name')->ignore($category)],
            'description' => 'nullable|string|max:1000',
            'enabled' => 'nullable|boolean',
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'enabled' => $request->boolean('enabled') ?? false,
        ]);

        return redirect()->route('championships.categories.index', $category->championship)
            ->with('flash.banner', __(':category updated.', [
                'category' => $category->name
            ]));
    }
}
