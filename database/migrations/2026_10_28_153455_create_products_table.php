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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->ulid()->unique();

            $table->timestamps();

            // ProductKind
            $table->string('kind', 30);

            $table->string('title', 250);

            $table->text('description')->nullable();

            // Required for enrollment products
            $table->foreignId('championship_id')->nullable();

            // Required for licence emission products
            $table->foreignId('licence_type_id')->nullable();

            // Inactive products are not listed and cannot be ordered
            $table->boolean('active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
