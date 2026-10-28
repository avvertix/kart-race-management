<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LicenceDocumentsDiskTest extends TestCase
{
    public function test_licence_documents_are_stored_outside_the_public_storage(): void
    {
        $disk = Storage::disk('licence-documents');
        $path = Str::random(16).'.pdf';

        try {
            $disk->put($path, 'identity card');

            $stored = Storage::disk('local')->path("licence-documents/{$path}");

            $this->assertFileExists($stored);
            $this->assertStringStartsNotWith(storage_path('app'.DIRECTORY_SEPARATOR.'public'), $stored);
            $this->assertStringStartsNotWith(public_path(), $stored);
        } finally {
            $disk->delete($path);
        }
    }
}
