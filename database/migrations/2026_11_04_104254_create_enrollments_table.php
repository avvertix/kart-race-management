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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            $table->foreignId('championship_id')->index();

            // The driver Licence enrolled; null while the Enrollment waits for the Licence Request
            $table->foreignId('licence_id')->nullable()->index();

            // The open Licence Request the Enrollment waits for; null once the Licence is known
            $table->foreignId('licence_request_id')->nullable()->index();

            // The Licence of the Competitor, required for underage drivers when the championship asks for it
            $table->foreignId('competitor_licence_id')->nullable();

            // The User who submitted the Enrollment: the driver's account, or the organizer enrolling on their behalf
            $table->foreignId('user_id');

            // EnrollmentStatus
            $table->string('status', 20);

            // The Order paying for the enrollment Product, when not free
            $table->foreignId('order_id')->nullable();

            $table->timestamp('confirmed_at')->nullable();

            $table->foreignId('confirmed_by')->nullable();

            $table->text('rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
