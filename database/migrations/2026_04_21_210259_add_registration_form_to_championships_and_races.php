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
            $table->string('registration_form')->nullable()->after('licences');
        });

        Schema::table('races', function (Blueprint $table) {
            $table->string('registration_form')->nullable()->after('red_flag');
        });
    }

    public function down(): void
    {
        Schema::table('championships', function (Blueprint $table) {
            $table->dropColumn('registration_form');
        });

        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn('registration_form');
        });
    }
};
