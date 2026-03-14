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
        Schema::table('participants', function (Blueprint $table) {
            $table->timestamp('payment_confirmed_at')->nullable()->after('payment_channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
