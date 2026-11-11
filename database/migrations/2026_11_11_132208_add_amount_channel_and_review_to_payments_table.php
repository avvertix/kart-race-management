<?php

declare(strict_types=1);

use App\Models\User;
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
        Schema::table('payments', function (Blueprint $table) {
            // Payments recorded by the organizer (cash, credit card) have no proof
            $table->text('path')->nullable()->change();
            $table->text('hash')->nullable()->change();

            // Payments of an Enrollment or a Licence Request only; race participant payments leave them empty
            $table->unsignedInteger('amount')->nullable();
            $table->unsignedSmallInteger('channel')->nullable();
            $table->string('status')->nullable();
            $table->foreignIdFor(User::class, 'recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['amount', 'channel', 'status', 'reviewed_at']);
        });
    }
};
