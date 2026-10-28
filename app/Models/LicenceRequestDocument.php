<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceRequestDocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * A file uploaded for a Licence Request: a document or a signed form.
 *
 * @property int $licence_request_id
 * @property int $licence_type_document_id
 * @property ?int $parent_index
 * @property string $name
 * @property LicenceDocumentKind $kind
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property string $hash
 * @property LicenceRequestDocumentStatus $status
 * @property ?string $note
 * @property-read LicenceRequest $licenceRequest
 */
class LicenceRequestDocument extends Model
{
    /** @use HasFactory<\Database\Factories\LicenceRequestDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'licence_type_document_id',
        'parent_index',
        'name',
        'kind',
        'path',
        'original_name',
        'mime',
        'size',
        'hash',
        'status',
        'note',
    ];

    /**
     * @return BelongsTo<LicenceRequest, $this>
     */
    public function licenceRequest(): BelongsTo
    {
        return $this->belongsTo(LicenceRequest::class);
    }

    /**
     * Delete the record and, once the transaction commits, its file.
     */
    public function deleteWithFile(): void
    {
        $path = $this->path;

        $this->delete();

        DB::afterCommit(fn () => Storage::disk('licence-documents')->delete($path));
    }

    /**
     * Whether the organizer flagged it, including once replaced and waiting to be reviewed again.
     */
    public function wasFlagged(): bool
    {
        return $this->status === LicenceRequestDocumentStatus::Flagged
            || ($this->status === LicenceRequestDocumentStatus::Pending && $this->note !== null);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_index' => 'integer',
            'kind' => LicenceDocumentKind::class,
            'size' => 'integer',
            'status' => LicenceRequestDocumentStatus::class,
        ];
    }
}
