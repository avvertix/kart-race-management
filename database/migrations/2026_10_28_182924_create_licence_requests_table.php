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

            // The licence Product picked, plain or Package
            $table->foreignId('product_id');

            $table->foreignId('licence_type_id');

            // LicenceLevel, ACI only
            $table->string('level', 20)->nullable();

            $table->foreignId('licence_grade_id')->nullable();

            // Grade written by the User when not in the list
            $table->string('grade_other', 250)->nullable();

            // LicenceRequestStatus
            $table->string('status', 30);

            // Encrypted HolderData, null until the holder section is saved
            $table->text('holder')->nullable();

            // Encrypted list of HolderData, underage holders only
            $table->text('parents')->nullable();

            $table->foreignId('order_id')->nullable();

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
