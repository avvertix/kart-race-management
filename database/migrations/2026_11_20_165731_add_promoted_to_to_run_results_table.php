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
        Schema::table('run_results', function (Blueprint $table) {
            // Run type a heat is scored and published as (pre-final or final), null when it is only a working session
            $table->unsignedTinyInteger('promoted_to')->nullable()->after('run_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('run_results', function (Blueprint $table) {
            $table->dropColumn('promoted_to');
        });
    }
};
