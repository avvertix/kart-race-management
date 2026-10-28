<?php

declare(strict_types=1);

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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignId('product_id');

            // Either a fixed amount or a range, in cents
            $table->unsignedInteger('amount')->nullable();

            $table->unsignedInteger('amount_min')->nullable();

            $table->unsignedInteger('amount_max')->nullable();

            $table->string('currency', 3);

            $table->timestamp('starts_at');

            // Valid until further notice when null
            $table->timestamp('ends_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
