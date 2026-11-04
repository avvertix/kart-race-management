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
        Schema::table('bib_reservations', function (Blueprint $table) {
            // BibReservationStatus; existing reservations become confirmed
            $table->string('status')->default('confirmed')->after('bib');

            // The Enrollment that picked the number, if any
            $table->foreignId('enrollment_id')->nullable()->after('championship_id');

            // The Licence the reservation belongs to, once known
            $table->foreignId('licence_id')->nullable()->after('enrollment_id');

            $table->foreignId('created_by_user_id')->nullable()->after('licence_id');

            $table->timestamp('released_at')->nullable()->after('reservation_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bib_reservations', function (Blueprint $table) {
            $table->dropColumn(['status', 'enrollment_id', 'licence_id', 'created_by_user_id', 'released_at']);
        });
    }
};
