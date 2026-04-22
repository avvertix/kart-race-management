<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->boolean('bonus_enabled')->nullable()->after('registration_form');
        });

        Schema::table('championships', function (Blueprint $table) {
            $table->boolean('bonus_enabled')->nullable()->after('registration_form');
        });
    }

    public function down(): void
    {
        // We don't want to lose data, so we won't drop the columns on down.
    }
};
