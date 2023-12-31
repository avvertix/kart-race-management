<?php

use App\Models\Championship;
use App\Models\ChampionshipTire;
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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            
            $table->ulid()->index();

            $table->timestamps();

            $table->foreignIdFor(Championship::class)->index();


            // Category name
            $table->string('name', 250);

            // Category is enabled and selectable by participants
            $table->boolean('enabled')->default(true);


            // Short name for time keeping
            $table->string('short_name', 250)->nullable();

            // Key used in previous configuration
            $table->string('code', 250)->nullable();

            // Additional description and notes
            $table->text('description')->nullable();

            // Tire to be used for the category
            $table->foreignIdFor(ChampionshipTire::class)->nullable();
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
