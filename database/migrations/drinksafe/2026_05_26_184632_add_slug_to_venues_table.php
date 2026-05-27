<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a slug column to the venues table:
     * 1. Add nullable slug column
     * 2. Generate unique slugs for existing venues
     * 3. Make slug NOT NULL
     * 4. Add unique index
     */
    public function up(): void
    {
        // Step 1: Add nullable slug column after name
        Schema::table('venues', function (Blueprint $table): void {
            $table->string('slug', 300)->nullable()->after('name');
        });

        // Step 2: Generate slugs for existing venues
        $this->generateSlugsForExistingVenues();

        // Step 3: Make slug NOT NULL and add unique index
        Schema::table('venues', function (Blueprint $table): void {
            $table->string('slug', 300)->nullable(false)->change();
            $table->unique('slug', 'idx_venues_slug_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table): void {
            $table->dropUnique('idx_venues_slug_unique');
            $table->dropColumn('slug');
        });
    }

    /**
     * Generate unique slugs for all existing venues.
     *
     * Uses a counter suffix to ensure uniqueness when duplicate slugs would occur.
     */
    private function generateSlugsForExistingVenues(): void
    {
        $existingSlugs = [];

        DB::table('venues')
            ->orderBy('created_at')
            ->orderBy('uuid')
            ->cursor()
            ->each(function (object $venue) use (&$existingSlugs): void {
                $baseSlug = Str::slug($venue->name);
                $slug = $baseSlug;
                $counter = 1;

                while (in_array($slug, $existingSlugs, true)) {
                    $slug = sprintf('%s-%d', $baseSlug, $counter);
                    $counter++;
                }

                $existingSlugs[] = $slug;

                DB::table('venues')
                    ->where('uuid', $venue->uuid)
                    ->update(['slug' => $slug]);
            });
    }
};
