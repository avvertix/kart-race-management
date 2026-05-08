<?php

declare(strict_types=1);

use App\Models\Championship;
use App\Models\Race;
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
        Schema::create('race_communications', function (Blueprint $table) {
            $table->id();

            $table->ulid()->index();

            $table->foreignIdFor(Race::class)->index()->constrained();
            $table->foreignIdFor(Championship::class)->index()->constrained();
            $table->foreignId('user_id')->index()->constrained('users');

            $table->string('type', 50);
            $table->tinyInteger('run_type')->nullable();
            $table->text('message');
            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down
    }
};
