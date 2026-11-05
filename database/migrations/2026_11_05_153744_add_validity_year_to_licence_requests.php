<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Existing requests are for the year renewals were open for when submitted, or when started.
     */
    public function up(): void
    {
        Schema::table('licence_requests', function (Blueprint $table) {
            // The year the requested Licence is valid for, chosen when the request is started
            $table->unsignedSmallInteger('validity_year')->nullable()->after('provider');
        });

        DB::table('licence_requests')
            ->select(['id', 'submitted_at', 'created_at'])
            ->orderBy('id')
            ->each(function (object $row) {
                $date = CarbonImmutable::parse($row->submitted_at ?? $row->created_at, 'UTC')->setTimezone(config('races.timezone'));

                DB::table('licence_requests')->where('id', $row->id)->update([
                    'validity_year' => $date->month === 12 ? $date->year + 1 : $date->year,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licence_requests', function (Blueprint $table) {
            $table->dropColumn('validity_year');
        });
    }
};
