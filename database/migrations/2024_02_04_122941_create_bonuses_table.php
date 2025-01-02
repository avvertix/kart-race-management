<?php

declare(strict_types=1);

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
        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(Championship::class)->index();

            $table->string('driver_licence_hash', 250)->index();

            $table->mediumText('driver_licence'); // an encrypted string

            $table->string('driver'); // Driver name

            $table->string('contact_email')->nullable();

            $table->unsignedTinyInteger('bonus_type');

            $table->integer('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};
