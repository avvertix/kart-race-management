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
        Schema::create('order_lines', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignId('order_id');

            $table->foreignId('product_id');

            // The Price locked when the order was placed
            $table->foreignId('price_id');

            $table->string('description', 250);

            // In cents, null until the organizer sets the price of a ranged order
            $table->unsignedInteger('amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_lines');
    }
};
