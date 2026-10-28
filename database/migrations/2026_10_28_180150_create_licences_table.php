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
        Schema::create('licences', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            // The User managing the licence, not necessarily its holder
            $table->foreignId('user_id');

            // LicenceProvider
            $table->string('provider', 20);

            $table->foreignId('licence_type_id')->nullable();

            // LicenceRole
            $table->string('role', 20);

            // LicenceLevel, ACI only
            $table->string('level', 20)->nullable();

            $table->foreignId('licence_grade_id')->nullable();

            // Grade written by the User when not in the list
            $table->string('grade_other', 250)->nullable();

            // DriverLicence used in race registration, driver licences only
            $table->unsignedTinyInteger('race_licence_category')->nullable();

            // Encrypted
            $table->text('number');

            // sha512 of the number, as participants.driver_licence
            $table->string('number_hash', 128);

            $table->unsignedSmallInteger('validity_year');

            // Encrypted HolderData
            $table->text('holder');

            // LicenceSource
            $table->string('source', 20);

            $table->timestamp('verified_at')->nullable();

            $table->foreignId('verified_by')->nullable();

            $table->text('temporary_licence_path')->nullable();

            $table->foreignId('licence_request_id')->nullable();

            $table->unique(['number_hash', 'validity_year', 'provider']);

            $table->index(['user_id', 'validity_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licences');
    }
};
