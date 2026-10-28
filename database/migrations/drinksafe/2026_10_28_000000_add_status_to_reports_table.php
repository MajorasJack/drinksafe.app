<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            // Existing reports become pending on purpose, so each one is
            // reviewed before it shows on the site again.
            $table->string('status', 16)->default('pending')->after('description');
            $table->timestamp('reviewed_at')->nullable()->after('status');

            $table->index(['status', 'created_at'], 'idx_reports_status_created');
        });
    }

    public function down(): void
    {
        // Without a status, pending and rejected reports would be public again, so take them down first.
        DB::table('reports')->whereIn('status', ['pending', 'rejected'])->update(['deleted_at' => now()]);

        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex('idx_reports_status_created');
            $table->dropColumn(['status', 'reviewed_at']);
        });
    }
};
