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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignIdFor(Participant::class);
            
            $table->unsignedTinyInteger('licence_type')->nullable();

            $table->text('licence_number');

            $table->date('licence_renewed_at')->nullable();

            $table->string('nationality', 250);

            $table->string('name', 250);

            $table->string('surname', 250);

            $table->text('email')->nullable();

            $table->text('phone')->nullable();
                        
            $table->text('birth_date')->nullable();
            
            $table->text('birth_place')->nullable();
            
            $table->text('medical_certificate_expiration_date')->nullable();
            
            $table->text('residence_address')->nullable();
            
            $table->text('sex')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};
