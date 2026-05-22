<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('italian_postal_codes', function (Blueprint $table) {
            $table->id();
            $table->string('cap', 5)->unique();
            $table->string('region', 30);
            $table->string('province_code', 2);
            $table->string('municipality', 255);
            $table->string('province');
        });
    }

    public function down(): void {}
};
