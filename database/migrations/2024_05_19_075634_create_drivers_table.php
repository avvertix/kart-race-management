<?php

declare(strict_types=1);

use App\Models\Championship;
use App\Models\Driver;
use App\Models\User;
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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(Championship::class);

            $table->foreignIdFor(User::class)->nullable();

            $table->unsignedInteger('bib')->nullable();

            $table->string('code', 10)->index();

            $table->string('email', 250);

            $table->string('phone', 250);

            $table->string('first_name', 250);

            $table->string('last_name', 250);

            $table->string('fiscal_code', 250)->nullable();

            $table->string('licence_number', 250);

            $table->string('licence_hash', 250)->index();

            $table->unsignedTinyInteger('licence_type')->nullable();

            $table->string('birth_date_hash', 250)->nullable();

            $table->date('medical_certificate_expiration_date')->nullable();

            $table->mediumText('birth')->nullable();

            $table->mediumText('address')->nullable();

            $table->unique(['championship_id', 'licence_hash'], 'licence_unique_in_championship');

        });

        Schema::table('participants', function (Blueprint $table) {
            $table->foreignIdFor(Driver::class)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
