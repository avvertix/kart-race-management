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
            // The last time the organizer asked for changes, to tell the files uploaded since
            $table->timestamp('changes_requested_at')->nullable()->after('submitted_at');
        });

        Schema::table('licence_request_documents', function (Blueprint $table) {
            // When the current file was uploaded; reviewing it does not change it
            $table->timestamp('uploaded_at')->nullable()->after('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licence_request_documents', function (Blueprint $table) {
            $table->dropColumn('uploaded_at');
        });

        Schema::table('licence_requests', function (Blueprint $table) {
            $table->dropColumn('changes_requested_at');
        });
    }
};
