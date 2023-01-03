<?php

use App\Models\Participant;
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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Participant::class);

            $table->string('chassis_manufacturer', 250);
            $table->string('engine_manufacturer', 250);
            $table->string('engine_model', 250);
            $table->string('oil_manufacturer', 250);
            $table->string('oil_type', 250);
            $table->string('oil_percentage', 250);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
