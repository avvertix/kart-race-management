<?php

declare(strict_types=1);

use App\Models\Championship;
use App\Models\User;
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
        Schema::create('orbits_backups', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(User::class)->nullable();

            $table->foreignIdFor(Championship::class)->nullable();

            $table->text('filename');

            $table->text('path');

            $table->text('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orbits_backups');
    }
};
