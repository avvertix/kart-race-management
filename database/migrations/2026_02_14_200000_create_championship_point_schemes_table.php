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
        Schema::create('championship_point_schemes', function (Blueprint $table) {
            $table->id();

            $table->ulid()->index();

            $table->foreignIdFor(Championship::class)->index()->constrained();

            $table->timestamps();

            $table->string('name', 250);

            $table->json('points_config');
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
