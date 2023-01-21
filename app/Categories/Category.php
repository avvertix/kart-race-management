<?php

namespace App\Categories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;

/**
 * @property string $name
 * @property string $tires
 */
class Category extends Fluent
{
    protected static $categories = null;
    
    /**
     * Get all defined categories
     */
    public static function all(): Collection 
    {
        self::$categories = collect(config('categories.default'))
            ->merge(json_decode(Storage::disk(config('categories.disk'))->get(config('categories.file')), true))
            ->mapInto(Category::class);
        
        return self::$categories;
    }

    /**
     * Get a category by its key
     */
    public static function find($key): Category|null 
    {
        return self::all()->get($key);
    }
    
}
