<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Participant;
use App\Models\RunResult;
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
        Schema::create('participant_results', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            // Foreign key to run result
            $table->foreignIdFor(RunResult::class)->constrained()->cascadeOnDelete();

            // Optional foreign key to participant (nullable, linked via racer_hash)
            $table->foreignIdFor(Participant::class)->nullable();

            // Optional foreign key to category (nullable, linked via timekeep_label)
            $table->foreignIdFor(Category::class)->nullable();

            // Common fields (both qualifying and race)
            $table->unsignedInteger('bib');
            $table->unsignedTinyInteger('status'); // ResultStatus enum
            $table->string('name');
            $table->string('category');
            $table->string('position'); // can be number, DSQ, DNF, DNS
            $table->string('position_in_category'); // can be number, DSQ, DNF, DNS
            $table->string('gap_from_leader');
            $table->string('gap_from_previous');
            $table->string('best_lap_time');
            $table->string('best_lap_number');
            $table->string('racer_hash')->index();
            $table->boolean('is_dnf')->default(false);
            $table->boolean('is_dns')->default(false);
            $table->boolean('is_dq')->default(false);
            $table->decimal('points', 8, 2)->nullable();

            // Race-specific fields (nullable for qualifying results)
            $table->unsignedInteger('laps')->nullable();
            $table->string('total_race_time')->nullable();

            // Qualifying-specific fields (nullable for race results)
            $table->string('second_best_time')->nullable();
            $table->string('second_best_lap_number')->nullable();
            $table->decimal('best_speed', 8, 2)->nullable();
            $table->decimal('second_best_speed', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_results');
    }
};
