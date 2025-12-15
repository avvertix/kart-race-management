<?php

declare(strict_types=1);

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
        Schema::create('run_results', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            // Foreign key to race
            $table->foreignIdFor(Race::class);

            // Session/run type (for sorting and identification)
            $table->unsignedInteger('run_type');

            // Session title (extracted from filename)
            $table->string('title');

            // Optional: track source file for reference
            $table->string('file_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('run_results');
    }
};
