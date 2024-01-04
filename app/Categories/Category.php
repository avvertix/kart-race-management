<?php

namespace App\Categories;

use App\Models\TireOption;
use App\Support\Describable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * @property string $name
 * @property string $description
 * @property string $tires
 * 
 * 
 */
class Category extends Fluent implements Describable
{
    protected static $categories = null;


    public function description(): string
    {
        return $this->get('description', '') ?? '';
    }

    public function tire(): TireOption
    {
        if($this->get('tire_name') && $this->get('tire_price')){
            
            return new TireOption([
                'name' => $this->get('tire_name'),
                'price' => $this->get('tire_price'),
            ]);
        }

        return TireOption::find($this->tires);
    }

    
    /**
     * Get all defined categories
     * 
     * @deprecated Use \App\Models\Category
     */
    public static function all(): Collection 
    {
        self::$categories = collect(config('categories.default'))
            ->merge(json_decode(Storage::disk(config('categories.disk'))->get(config('categories.file')) ?? '{}', true))
            ->mapInto(Category::class);
        
        return self::$categories;
    }

    /**
     * Get a category by its key
     * 
     * @deprecated Use \App\Models\Category
     */
    public static function find($key): Category|null 
    {
        return self::all()->get($key);
    }

    /**
     * @deprecated Use \App\Models\Category
     */
    public static function search($term): Collection
    {
        return self::all()->filter(function($value, $key) use ($term){
            return Str::contains($value->get('name'), $term, true)
                || Str::contains($value->description(), $term, true);
        });
    }
    
}
