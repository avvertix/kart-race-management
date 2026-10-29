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
        Schema::create('licence_requests', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            // The User who requests, not necessarily the holder
            $table->foreignId('user_id');

            // LicenceProvider, null until the licence section is saved
            $table->string('provider', 20)->nullable();

            // Code of the Licence Type in config/licences.php
            $table->string('licence_type', 50)->nullable();

            // ACI driver licences only
            $table->boolean('includes_competitor')->default(false);

            // The number of the licence being renewed, encrypted
            $table->text('previous_licence_number')->nullable();

            // sha512 of the previous number, as licences.number_hash
            $table->string('previous_licence_number_hash', 128)->nullable()->index();

            // LicenceRequestStatus
            $table->string('status', 30);

            // Encrypted HolderData, null until the holder section is saved
            $table->text('holder')->nullable();

            // Encrypted list of HolderData, underage holders only
            $table->text('parents')->nullable();

            // LicenceRequestPaymentStatus
            $table->string('payment_status', 30);

            // Set by the organizer, in cents
            $table->unsignedInteger('amount')->nullable();

            $table->string('currency', 3)->default('EUR');

            $table->foreignId('priced_by')->nullable();

            $table->timestamp('priced_at')->nullable();

            $table->foreignId('payment_confirmed_by')->nullable();

            $table->timestamp('paid_at')->nullable();

            // The paid Promotional Package Order covering the renewal
            $table->foreignId('covering_order_id')->nullable();

            // The Licence created on emission
            $table->foreignId('licence_id')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamp('forwarded_at')->nullable();

            $table->timestamp('emitted_at')->nullable();

            $table->timestamp('rejected_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->foreignId('reviewed_by')->nullable();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licence_requests');
    }
};
