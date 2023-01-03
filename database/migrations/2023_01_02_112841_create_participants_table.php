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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();
            
            $table->foreignIdFor(Championship::class);

            $table->foreignIdFor(Race::class);

            $table->unsignedInteger('bib'); // or race number
            
            $table->string('category', 250);
            
            $table->string('name', 250);

            $table->string('surname', 250);

            $table->foreignIdFor(User::class, 'added_by')->nullable();

            $table->dateTime('confirmed_at')->nullable();
            
            $table->json('consents')->default('[]');

            //TODO: maybe insert the licence type here also to not query two entities?
                        
            // $table->text('signature')->nullable();
            
            // $table->text('signing_code')->nullable();
            
            // $table->text('signed_by')->nullable();

            // $table->dateTime('signed_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('participants');
    }
};
