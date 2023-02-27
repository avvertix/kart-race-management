<?php

use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trashed_participants', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();
            
            $table->foreignIdFor(Championship::class);

            $table->foreignIdFor(Race::class);

            $table->unsignedInteger('bib'); // or race number
            
            $table->string('category', 250);
            
            $table->string('first_name', 250);

            $table->string('last_name', 250);

            $table->string('driver_licence', 250);
            
            $table->string('competitor_licence', 250)->nullable();

            $table->mediumText('driver'); // an encrypted json

            $table->unsignedTinyInteger('licence_type')->nullable();

            $table->mediumText('competitor')->nullable(); // an encrypted json

            $table->mediumText('mechanic')->nullable(); // an encrypted json
            
            $table->json('vehicles')->nullable(); // the list of vehicles that a participant can have (minimum 1, maximum 2). Properties chassis_manufacturer, engine_manufacturer, engine_model, oil_manufacturer, oil_type, oil_percentage
            
            $table->dateTime('confirmed_at')->nullable();
            
            $table->json('consents')->default('[]');
            
            $table->foreignIdFor(User::class, 'added_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trashed_participants');
    }
};
