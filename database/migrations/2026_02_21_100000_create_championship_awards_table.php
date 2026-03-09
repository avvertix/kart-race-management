<?php

declare(strict_types=1);

use App\Models\Category;
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
        Schema::create('championship_awards', function (Blueprint $table) {
            $table->id();

            $table->ulid()->index();

            $table->foreignIdFor(Championship::class)->index()->constrained();

            $table->string('name', 250);

            $table->string('type', 20);

            $table->string('ranking_mode', 20);

            $table->unsignedInteger('best_n')->nullable();

            $table->string('wildcard_filter', 20)->default('all');

            $table->foreignIdFor(Category::class)->nullable()->constrained();

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
