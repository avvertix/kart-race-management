<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\ChampionshipAward;
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
        Schema::create('championship_award_category', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(ChampionshipAward::class)->constrained()->cascadeOnDelete();

            $table->foreignIdFor(Category::class)->constrained();

            $table->unique(['championship_award_id', 'category_id'], 'award_category_unique');
        });

        Schema::create('championship_award_race', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(ChampionshipAward::class)->constrained()->cascadeOnDelete();

            $table->foreignIdFor(Race::class)->constrained();

            $table->unique(['championship_award_id', 'race_id'], 'award_race_unique');
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
