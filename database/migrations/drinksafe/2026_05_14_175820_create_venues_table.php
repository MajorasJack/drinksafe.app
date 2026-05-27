<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->string('name', 255);
            $table->string('city', 100);
            $table->string('address', 500)->nullable();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->timestamps();

            // Index for filtering by city
            $table->index('city', 'idx_venues_city');

            // Index for searching by name
            $table->index('name', 'idx_venues_name');

            // Composite index for common search patterns (city + name)
            $table->index(['city', 'name'], 'idx_venues_city_name');

            // Composite index for geospatial queries
            $table->index(['latitude', 'longitude'], 'idx_venues_location');

            // Full-text index for name search (SQLite doesn't support FULLTEXT, will work in MySQL/PostgreSQL)
            if (config('database.default') !== 'sqlite') {
                $table->fullText('name', 'idx_venues_fulltext_name');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
