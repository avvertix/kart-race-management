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
        Schema::table('championship_awards', function (Blueprint $table) {
            // Drivers must race at least best_n races to qualify for a best N category award
            $table->boolean('best_n_is_minimum')->default(false)->after('best_n');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('championship_awards', function (Blueprint $table) {
            $table->dropColumn('best_n_is_minimum');
        });
    }
};
