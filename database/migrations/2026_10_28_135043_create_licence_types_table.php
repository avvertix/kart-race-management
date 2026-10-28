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
        Schema::create('licence_types', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            // LicenceProvider, fixed after creation
            $table->string('provider', 20);

            // LicenceRole, fixed after creation
            $table->string('role', 20);

            $table->string('name', 250);

            $table->text('description')->nullable();

            // DriverLicence used in race registration, only for ASI driver types. ACI derives it from the grade level
            $table->unsignedTinyInteger('race_licence_category')->nullable();

            // Inactive types are not offered for new requests
            $table->boolean('active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down.
    }
};
