<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            // Optional exact time of the incident (HH:MM). Complements the
            // coarse time_of_day band. Nullable as users may not recall it.
            $table->time('incident_time')->nullable()->after('incident_date');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn('incident_time');
        });
    }
};
