<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceDocumentScope;
use App\Enums\LicenceFormTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A document or form a Licence Request needs.
 *
 * @property int $licence_type_id
 * @property string $name
 * @property ?string $description
 * @property LicenceDocumentKind $kind
 * @property ?LicenceFormTemplate $template
 * @property LicenceDocumentScope $scope
 * @property bool $underage_only
 * @property bool $required
 * @property int $sort
 */
class LicenceTypeDocument extends Model
{
    /** @use HasFactory<\Database\Factories\LicenceTypeDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'kind',
        'template',
        'scope',
        'underage_only',
        'required',
        'sort',
    ];

    /**
     * @return BelongsTo<LicenceType, $this>
     */
    public function licenceType(): BelongsTo
    {
        return $this->belongsTo(LicenceType::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => LicenceDocumentKind::class,
            'template' => LicenceFormTemplate::class,
            'scope' => LicenceDocumentScope::class,
            'underage_only' => 'boolean',
            'required' => 'boolean',
        ];
    }
}
