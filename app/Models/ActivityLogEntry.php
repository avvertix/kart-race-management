<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

class ActivityLogEntry extends Activity
{
    /**
     * Rows logged before the migration to the `attribute_changes` column keep
     * their tracked changes under `properties->attributes`/`properties->old`.
     *
     * @return array<string, mixed>
     */
    public function changedAttributes(): array
    {
        if ($this->getRawOriginal('attribute_changes') !== null) {
            return $this->attribute_changes->get('attributes', []);
        }

        return $this->properties->get('attributes', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function changedOldAttributes(): array
    {
        if ($this->getRawOriginal('attribute_changes') !== null) {
            return $this->attribute_changes->get('old', []);
        }

        return $this->properties->get('old', []);
    }
}
