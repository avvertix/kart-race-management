<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CopyChampionshipCategories;
use App\Models\Category;
use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CopyChampionshipCategoriesController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Category::class, 'category');
    }

    /**
     * Show the form for copying categories from another championship.
     */
    public function create(Championship $championship)
    {
        $this->authorize('create', Category::class);

        $sourceChampionships = Championship::query()
            ->where('id', '!=', $championship->id)
            ->whereHas('categories')
            ->orderBy('title', 'ASC')
            ->withCount('categories')
            ->get();

        return view('category.copy', [
            'championship' => $championship,
            'sourceChampionships' => $sourceChampionships,
        ]);
    }

    /**
     * Copy categories from another championship.
     */
    public function store(Request $request, Championship $championship, CopyChampionshipCategories $copyCategories)
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'source_championship' => [
                'required',
                'integer',
                Rule::exists((new Championship())->getTable(), 'id'),
            ],
        ]);

        $sourceChampionship = Championship::findOrFail($validated['source_championship']);

        $copiedCategories = $copyCategories($sourceChampionship, $championship);

        $categoriesCount = $copiedCategories->count();

        return redirect()->route('championships.categories.index', $championship)
            ->with('flash.banner', trans_choice(':value category copied|:value categories copied', $categoriesCount, [
                'count' => $categoriesCount,
            ]));
    }
}
