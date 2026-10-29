<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Data\RequiredDocument;
use App\Enums\LicenceRequestDocumentStatus;
use App\Models\LicenceRequest;
use App\Models\LicenceRequestDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class StoreLicenceRequestUpload
{
    /**
     * Store the file of a document or signed form the request asks for, replacing the previous one.
     * A replaced file waits for a new review and keeps the organizer's note.
     *
     * @throws ValidationException when the request does not ask for it, the file cannot be replaced or is not valid
     */
    public function __invoke(LicenceRequest $licenceRequest, RequiredDocument $required, UploadedFile $file): LicenceRequestDocument
    {
        $isAskedFor = $licenceRequest->requiredDocuments($required->document->kind())
            ->contains(fn (RequiredDocument $asked) => $asked->key() === $required->key());

        if (! $isAskedFor) {
            throw ValidationException::withMessages([
                'file' => __('The request does not ask for this document.'),
            ]);
        }

        $existing = $licenceRequest->uploadFor($required);

        if (! $licenceRequest->canReplaceUpload($existing)) {
            throw ValidationException::withMessages([
                'file' => __('This document was accepted and cannot be changed.'),
            ]);
        }

        Validator::make(['file' => $file], [
            'file' => [
                'required',
                File::types(['pdf', 'jpg', 'png'])->max(10 * 1024),
            ],
        ])->validate();

        $path = $file->store("licence-requests/{$licenceRequest->ulid}", 'licence-documents');

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => __('The file could not be saved, please try again.'),
            ]);
        }

        $attributes = [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => (int) $file->getSize(),
            'hash' => hash_file('sha512', Storage::disk('licence-documents')->path($path)),
            'status' => LicenceRequestDocumentStatus::Pending,
            'uploaded_at' => now(),
        ];

        $licenceRequest->unsetRelation('documents');

        if ($existing !== null) {
            Storage::disk('licence-documents')->delete($existing->path);

            $existing->update($attributes);

            return $existing;
        }

        return $licenceRequest->documents()->create([
            ...$attributes,
            'document' => $required->document,
            'parent_index' => $required->parentIndex,
        ]);
    }
}
