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
        Schema::table('licence_requests', function (Blueprint $table) {
            // How the payment was made: a bank transfer with a proof, or cash and credit card at the organizer's office
            $table->integer('payment_channel')->nullable()->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licence_requests', function (Blueprint $table) {
            $table->dropColumn('payment_channel');
        });
    }
};
