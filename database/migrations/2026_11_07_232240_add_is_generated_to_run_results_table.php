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
            // Results calculated from other results (combined series, classification of the heats) instead of uploaded
            $table->boolean('is_generated')->default(false)->after('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('run_results', function (Blueprint $table) {
            $table->dropColumn('is_generated');
        });
    }
};
