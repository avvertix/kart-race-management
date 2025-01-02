<?php

declare(strict_types=1);

use App\Models\Championship;
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
        Schema::create('bib_reservations', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(Championship::class)->index();

            $table->unsignedInteger('bib')->index(); // or race number

            $table->string('driver_licence_hash', 250)->nullable();

            $table->mediumText('driver_licence')->nullable(); // an encrypted string

            $table->string('driver'); // Driver name

            $table->string('contact_email')->nullable();

            $table->unsignedTinyInteger('licence_type')->nullable();

            $table->dateTime('reservation_expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bib_reservations');
    }
};
