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

            // LicenceDocument
            $table->string('document', 40);

            // Which parent the document is about, null for the holder's
            $table->unsignedTinyInteger('parent_index')->nullable();

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

            $table->unique(['licence_request_id', 'document', 'parent_index'], 'licence_request_documents_slot_unique');
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
