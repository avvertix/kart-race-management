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
        Schema::create('communication_messages', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->text('message');

            $table->string('theme')->nullable();

            $table->string('target_path')->nullable();

            $table->text('target_user_role')->nullable();

            $table->dateTime('starts_at')->nullable();

            $table->dateTime('ends_at')->nullable();

            $table->boolean('dismissable')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('communication_messages');
    }
};
