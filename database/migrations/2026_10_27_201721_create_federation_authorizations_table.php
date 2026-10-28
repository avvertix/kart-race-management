<?php

declare(strict_types=1);

use App\Models\Participant;
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
        Schema::create('federation_authorizations', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(Participant::class)->index();

            $table->text('path');

            $table->text('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federation_authorizations');
    }
};
