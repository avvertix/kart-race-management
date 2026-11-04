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
        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignId('championship_id')->index();

            // The Licence holding the Entitlement, once known
            $table->foreignId('licence_id')->nullable();

            // The licence number hash the Entitlement applies to, the same as participants.driver_licence
            $table->string('licence_hash', 250)->index();

            // EntitlementMode: credit (amounts are race fees) or balance (amounts in cents)
            $table->string('mode', 20);

            $table->unsignedInteger('amount');

            $table->unsignedInteger('remaining');

            // Where the Entitlement comes from: a claimed promo code or a package order line
            $table->nullableMorphs('source');
        });

        Schema::create('participant_entitlement', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignId('participant_id')->index();

            $table->foreignId('entitlement_id')->index();

            // The discount on the race fee, in cents
            $table->unsignedInteger('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_entitlement');
        Schema::dropIfExists('entitlements');
    }
};
