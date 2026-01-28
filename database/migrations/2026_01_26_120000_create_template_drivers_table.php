<?php

declare(strict_types=1);

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
        Schema::create('template_drivers', function (Blueprint $table) {
            $table->id();

            $table->ulid('uuid')->unique();

            $table->timestamps();

            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();

            $table->string('name', 250)->nullable(); // Label for the template

            $table->unsignedInteger('bib'); // or race number

            $table->mediumText('driver'); // an encrypted json

            $table->mediumText('competitor')->nullable(); // an encrypted json

            $table->mediumText('mechanic')->nullable(); // an encrypted json

            $table->index(['user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_drivers');
    }
};
