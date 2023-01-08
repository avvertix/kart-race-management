<?php

use App\Models\Championship;
use App\Models\Participant;
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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(Championship::class);

            $table->unsignedInteger('bib'); // or race number
            
            $table->string('category', 250);
            
            $table->string('first_name', 250);

            $table->string('last_name', 250);

            $table->unsignedTinyInteger('licence_type')->nullable();
            
            $table->text('licence_number');

            $table->date('licence_renewed_at')->nullable();

            $table->string('nationality', 250);

            $table->text('email');

            $table->text('phone');
                        
            $table->text('birth_date');
            
            $table->text('birth_place');
            
            $table->text('medical_certificate_expiration_date');
            
            $table->text('residence_address');
            
            $table->text('sex')->nullable();

            $table->foreignIdFor(User::class, 'added_by')->nullable();

            $table->string('team_name', 250)->nullable();
            
            $table->text('team_licence_number')->nullable();

            // TODO: how to handle versions as the category and/or licence number and/or medical certificate can change over the year?
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
