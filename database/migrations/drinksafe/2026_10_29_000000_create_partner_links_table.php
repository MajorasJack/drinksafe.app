<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_links', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('label', 100);
            $table->string('destination')->default('/submit-report');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // No timestamps: an updated_at would record the time of the day's last scan.
        Schema::create('partner_link_daily_counts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_link_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('count')->default(0);

            $table->unique(['partner_link_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_link_daily_counts');
        Schema::dropIfExists('partner_links');
    }
};
