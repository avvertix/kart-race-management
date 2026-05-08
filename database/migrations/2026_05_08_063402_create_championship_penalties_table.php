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
        Schema::create('championship_penalties', function (Blueprint $table) {
            $table->id();

            $table->ulid()->index();

            $table->foreignIdFor(Championship::class)->index()->constrained();

            $table->string('title', 250);
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down
    }
};
