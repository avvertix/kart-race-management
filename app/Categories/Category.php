<?php

declare(strict_types=1);

namespace App\Categories;

use App\Models\TireOption;
use App\Support\Describable;
use Illuminate\Support\Fluent;

/**
 * @property string $name
 * @property string $description
 * @property string $tires
 */
class Category extends Fluent implements Describable
{
    protected static $categories = null;

    public function description(): string
    {
        return $this->get('description', '') ?? '';
    }

    public function tire(): ?TireOption
    {
        if ($this->get('tire_name') && $this->get('tire_price')) {

            return new TireOption([
                'name' => $this->get('tire_name'),
                'price' => $this->get('tire_price'),
            ]);
        }

        return TireOption::find($this->tires);
    }
}
