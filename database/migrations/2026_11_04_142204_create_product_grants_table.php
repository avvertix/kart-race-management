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
        Schema::create('product_grants', function (Blueprint $table) {
            $table->id();

            $table->timestamps();

            $table->foreignId('product_id')->index();

            // ProductGrantKind
            $table->string('kind', 30);

            // The championship of an Enrollment or Entitlement grant
            $table->foreignId('championship_id')->nullable();

            // EntitlementMode and amount (race fees or cents) of an Entitlement grant
            $table->string('mode', 20)->nullable();

            $table->unsignedInteger('amount')->nullable();

            // Whether an Enrollment grant lets the driver reserve a race number, also when the championship doesn't
            $table->boolean('grants_bib_reservation')->default(false);
        });

        Schema::table('entitlements', function (Blueprint $table) {
            // The package grant that created the Entitlement, so that granting twice creates nothing twice
            $table->foreignId('product_grant_id')->nullable()->after('source_id');

            $table->unique(['product_grant_id', 'source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entitlements', function (Blueprint $table) {
            $table->dropUnique(['product_grant_id', 'source_type', 'source_id']);
            $table->dropColumn('product_grant_id');
        });

        Schema::dropIfExists('product_grants');
    }
};
