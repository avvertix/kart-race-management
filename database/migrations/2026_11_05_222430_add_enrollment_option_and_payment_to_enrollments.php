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
        Schema::table('enrollments', function (Blueprint $table) {
            // The EnrollmentOption chosen, null when enrolled by the organizer
            $table->foreignId('enrollment_option_id')->nullable()->after('user_id');

            // The amount to pay in cents, copied from the option when submitting
            $table->unsignedInteger('amount')->default(0)->after('enrollment_option_id');

            $table->string('currency', 3)->default('EUR')->after('amount');

            // EnrollmentPaymentStatus
            $table->string('payment_status', 30)->default('not_due')->after('currency');

            $table->timestamp('paid_at')->nullable()->after('payment_status');

            $table->foreignId('payment_confirmed_by')->nullable()->after('paid_at');
        });

        Schema::table('licence_requests', function (Blueprint $table) {
            // The Enrollment whose option covers the renewal, so the request asks for no payment of its own
            $table->foreignId('covering_enrollment_id')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licence_requests', function (Blueprint $table) {
            $table->dropColumn('covering_enrollment_id');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['enrollment_option_id', 'amount', 'currency', 'payment_status', 'paid_at', 'payment_confirmed_by']);
        });
    }
};
