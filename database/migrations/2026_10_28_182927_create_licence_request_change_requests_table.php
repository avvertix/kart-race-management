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
        Schema::create('licence_request_change_requests', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignId('licence_request_id');

            // LicenceRequestSection
            $table->string('section', 20);

            // What the organizer asks to change, shown to the requester
            $table->text('note');

            // Set when the requester saves the flagged section
            $table->timestamp('resolved_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licence_request_change_requests');
    }
};
