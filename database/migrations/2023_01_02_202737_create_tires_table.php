<?php

declare(strict_types=1);

use App\Models\Participant;
use App\Models\Race;
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
        Schema::create('tires', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->ulid('uuid')->unique();

            $table->foreignIdFor(Participant::class);

            $table->foreignIdFor(Race::class);

            $table->string('code', 250);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tires');
    }
};
