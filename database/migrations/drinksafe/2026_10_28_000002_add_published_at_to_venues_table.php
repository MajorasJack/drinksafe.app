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
        Schema::table('venues', function (Blueprint $table): void {
            $table->timestamp('published_at')->nullable()->after('longitude');

            $table->index('published_at', 'idx_venues_published_at');
        });

        DB::table('venues')->update(['published_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropIndex('idx_venues_published_at');
            $table->dropColumn('published_at');
        });
    }
};
