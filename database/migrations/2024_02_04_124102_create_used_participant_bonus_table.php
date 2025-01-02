<?php

declare(strict_types=1);

use App\Models\Bonus;
use App\Models\Participant;
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
        Schema::create('participant_bonus', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(Participant::class);

            $table->foreignIdFor(Bonus::class);

            $table->unsignedTinyInteger('bonus_type')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_bonus');
    }
};
