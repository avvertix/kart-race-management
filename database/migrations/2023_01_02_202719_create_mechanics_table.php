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
        Schema::create('mechanics', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Participant::class);

            $table->unsignedTinyInteger('licence_type')->nullable();

            $table->text('licence_number');

            $table->date('licence_renewed_at')->nullable();

            $table->string('nationality', 250);

            $table->string('name', 250);

            $table->string('surname', 250);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mechanics');
    }
};
