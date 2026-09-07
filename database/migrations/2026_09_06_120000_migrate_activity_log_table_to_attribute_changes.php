<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->json('attribute_changes')->nullable()->after('causer_id');
        });

        // Existing rows keep their 'attributes'/'old' data under `properties` instead of
        // being backfilled into `attribute_changes` here — the table has 13k+ rows and
        // isn't worth a bulk rewrite. App\Models\ActivityLogEntry reads both shapes.

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('batch_uuid');
        });
    }

    public function down(): void
    {
        // We don't want to lose data, so we won't drop or revert the columns on down.
    }
};
