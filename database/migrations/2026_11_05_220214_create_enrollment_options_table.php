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
        Schema::create('enrollment_options', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            $table->foreignId('championship_id')->index();

            $table->string('title', 150);

            $table->text('description')->nullable();

            // Offered to drivers; an inactive option stays on the Enrollments that chose it
            $table->boolean('active')->default(true);

            $table->unsignedSmallInteger('sort')->default(0);

            // Fixed amount in cents, 0 for a free enrollment
            $table->unsignedInteger('amount')->default(0);

            $table->string('currency', 3)->default('EUR');

            // Deposit in cents that unlocks the Enrollment, the balance due by balance_due_on; null when paid at once
            $table->unsignedInteger('deposit_amount')->nullable();

            $table->date('balance_due_on')->nullable();

            // Whether the amount also pays the Licence Request the Enrollment waits for
            $table->boolean('covers_licence_renewal')->default(false);

            // Whether the Enrollment lets the driver reserve a Championship BIB
            $table->boolean('reserves_race_number')->default(false);

            // Race fee discount granted with the Enrollment: EntitlementMode and its amount (race fees or cents)
            $table->string('entitlement_mode', 20)->nullable();

            $table->unsignedInteger('entitlement_amount')->nullable();

            // The DriverLicence categories the option is offered to, empty for any
            $table->json('licence_categories')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_options');
    }
};
