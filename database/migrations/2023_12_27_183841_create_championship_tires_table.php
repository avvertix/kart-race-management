<?php

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
        Schema::create('championship_tires', function (Blueprint $table) {
            $table->id();
            
            $table->ulid()->index();

            $table->foreignIdFor(Championship::class)->index();

            $table->timestamps();
            
            // Name of the tire set
            $table->string('name', 250);

            // Cost of a tire set in cents
            $table->unsignedInteger('price');
            
            // Original key as in the previous configuration
            $table->string('code', 250)->nullable()->index();

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
