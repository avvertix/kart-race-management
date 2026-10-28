<?php

declare(strict_types=1);

use App\Models\LicenceType;
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
        Schema::create('licence_type_documents', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignIdFor(LicenceType::class)->index();

            $table->string('name', 250);

            $table->text('description')->nullable();

            // LicenceDocumentKind: upload or signed_form
            $table->string('kind', 20);

            // LicenceFormTemplate used to pre-fill a signed form
            $table->string('template', 50)->nullable();

            // LicenceDocumentScope: holder, each_parent or once
            $table->string('scope', 20);

            // Asked only when the holder is underage
            $table->boolean('underage_only')->default(false);

            $table->boolean('required')->default(true);

            $table->unsignedSmallInteger('sort')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down.
    }
};
