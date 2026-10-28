<?php

declare(strict_types=1);

use App\Models\LicenceType;
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
        Schema::create('licence_grades', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(LicenceType::class)->index();

            // LicenceLevel, only for ACI types
            $table->string('level', 20)->nullable();

            $table->string('name', 250);

            $table->unsignedSmallInteger('sort')->default(0);
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
