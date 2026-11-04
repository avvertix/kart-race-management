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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            // Stored uppercase, so codes are case-insensitive and unique across the platform
            $table->string('code', 20)->unique();

            $table->foreignId('championship_id')->index();

            // EntitlementMode: credit (amount is a number of race fees) or balance (amount in cents)
            $table->string('mode', 20);

            $table->unsignedInteger('amount');

            // PromoCodeStatus
            $table->string('status', 20);

            $table->timestamp('claimed_at')->nullable();

            $table->foreignId('claimed_by_user_id')->nullable();

            // The Licence the code was claimed for, or the hash of the licence number typed in a race registration
            $table->foreignId('licence_id')->nullable();

            $table->string('licence_hash', 250)->nullable();

            $table->foreignId('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
