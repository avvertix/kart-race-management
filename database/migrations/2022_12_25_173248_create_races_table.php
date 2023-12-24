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
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();

            $table->timestamps();
            $table->timestamp('event_start_at')->nullable();
            $table->timestamp('event_end_at')->nullable(); // in case it starts on saturday and end on sunday
            
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();

            $table->foreignIdFor(Championship::class);

            $table->string('track'); // race track

            $table->string('title')->nullable();
            $table->mediumText('description')->nullable();

            $table->json('tags')->default('[]');
            
            $table->json('properties')->default('[]');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('races');
    }
};
