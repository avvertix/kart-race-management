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
        Schema::table('bonuses', function (Blueprint $table) {
            $table->mediumText('driver_licence')->nullable()->change();
            $table->string('driver_licence_hash', 250)->nullable()->change();
            $table->mediumText('driver_fiscal_code')->nullable()->after('driver_licence');
            $table->string('driver_fiscal_code_hash', 250)->nullable()->index()->after('driver_fiscal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
