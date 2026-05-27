<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->uuid('uuid')->primary();
            $table->uuid('venue_uuid');
            $table->date('incident_date');
            $table->enum('time_of_day', ['Morning', 'Afternoon', 'Evening', 'Night', 'Unknown']);
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraint with cascade delete
            $table->foreign('venue_uuid', 'fk_reports_venue_uuid')
                ->references('uuid')
                ->on('venues')
                ->onDelete('cascade');

            // Composite index for venue detail page queries (venue + date DESC)
            $table->index(['venue_uuid', 'incident_date'], 'idx_reports_venue_date');

            // Composite index for recent reports with soft deletes
            $table->index(['deleted_at', 'created_at'], 'idx_reports_deleted_created');

            // Index for time of day filtering
            $table->index('time_of_day', 'idx_reports_time_of_day');

            // Full-text index for description search (SQLite doesn't support FULLTEXT, will work in MySQL/PostgreSQL)
            if (config('database.default') !== 'sqlite') {
                $table->fullText('description', 'idx_reports_fulltext_description');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
