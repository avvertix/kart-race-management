<?php

use App\Models\Participant;
use App\Models\Race;
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
        Schema::create('tires', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            $table->ulid()->unique();

            $table->foreignIdFor(Participant::class);

            $table->foreignIdFor(Race::class);

            $table->string('code', 250);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tires');
    }
};
