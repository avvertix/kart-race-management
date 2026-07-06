<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->json('registration_settings')->nullable()->after('bonus_enabled');
        });
    }

    public function down(): void
    {
        // We don't want to lose data, so we won't drop the columns on down.
    }
};
