<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trim the spaces off every venue's name and city.
 *
 * The Venue model trims both when they're saved, so the duplicate check can
 * compare the plain columns and use `idx_venues_city_name`. This brings older
 * rows in line. It only removes surrounding spaces and leaves `updated_at` as
 * it is. There's no `down()`, as the removed spaces can't be put back.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('venues')->update([
            'name' => DB::raw('TRIM(name)'),
            'city' => DB::raw('TRIM(city)'),
        ]);
    }
};
