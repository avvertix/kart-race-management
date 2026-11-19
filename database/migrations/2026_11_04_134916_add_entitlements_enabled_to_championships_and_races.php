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
        Schema::table('championships', function (Blueprint $table) {
            // Entitlements from promo codes and enrollment options apply to race fees; requires bonuses off
            $table->boolean('entitlements_enabled')->default(false)->after('bonus_enabled');
        });

        Schema::table('races', function (Blueprint $table) {
            // Null follows the championship, false opts the race out
            $table->boolean('entitlements_enabled')->nullable()->after('bonus_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropColumn('entitlements_enabled');
        });

        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('entitlements_enabled');
        });
    }
};
