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
        Schema::table('licence_requests', function (Blueprint $table) {
            // When the uploaded documents and payment proofs were deleted after the retention period
            $table->timestamp('documents_pruned_at')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licence_requests', function (Blueprint $table) {
            $table->dropColumn('documents_pruned_at');
        });
    }
};
