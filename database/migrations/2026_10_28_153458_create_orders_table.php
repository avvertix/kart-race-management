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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            $table->foreignId('user_id');

            // OrderStatus
            $table->string('status', 30);

            // In cents, null until the organizer sets the price of a ranged order
            $table->unsignedInteger('total')->nullable();

            $table->string('currency', 3);

            // Shown to the user when the price is outside the locked range
            $table->text('price_override_reason')->nullable();

            $table->foreignId('priced_by')->nullable();

            $table->timestamp('priced_at')->nullable();

            $table->foreignId('confirmed_by')->nullable();

            $table->timestamp('paid_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
