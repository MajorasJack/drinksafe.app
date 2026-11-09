<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->timestamp('unpublished_at')->nullable()->after('published_at');

            $table->index('unpublished_at', 'idx_venues_unpublished_at');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropIndex('idx_venues_unpublished_at');
            $table->dropColumn('unpublished_at');
        });
    }
};
