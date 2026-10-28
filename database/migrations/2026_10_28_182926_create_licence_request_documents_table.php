<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licence_request_documents', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignId('licence_request_id');

            $table->foreignId('licence_type_document_id');

            // Which parent the document is about, for documents asked for each parent
            $table->unsignedTinyInteger('parent_index')->nullable();

            // Copied from the Licence Type document, which can be removed later
            $table->string('name', 250);

            // LicenceDocumentKind, copied from the Licence Type document
            $table->string('kind', 20);

            // On the licence-documents disk
            $table->text('path');

            $table->string('original_name', 250);

            $table->string('mime', 100);

            $table->unsignedInteger('size');

            // sha512 of the file
            $table->string('hash', 128);

            // LicenceRequestDocumentStatus
            $table->string('status', 20);

            // The organizer's note when flagged
            $table->text('note')->nullable();

            $table->unique(['licence_request_id', 'licence_type_document_id', 'parent_index'], 'licence_request_documents_slot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licence_request_documents');
    }
};
