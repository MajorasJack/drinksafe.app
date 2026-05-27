<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('has venues table', function (): void {
    expect(Schema::hasTable('venues'))->toBeTrue();
});

it('has correct columns in venues table', function (): void {
    $columns = ['uuid', 'name', 'city', 'address', 'latitude', 'longitude', 'created_at', 'updated_at'];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('venues', $column))->toBeTrue();
    }
});

it('has uuid as primary key', function (): void {
    // Check table info to see if uuid is the primary key
    $columns = DB::select("PRAGMA table_info('venues')");
    $uuidIsPrimaryKey = false;

    foreach ($columns as $column) {
        if ($column->name === 'uuid' && $column->pk === 1) {
            $uuidIsPrimaryKey = true;
            break;
        }
    }

    expect($uuidIsPrimaryKey)->toBeTrue();
});

it('has city index', function (): void {
    $indexes = DB::select("PRAGMA index_list('venues')");
    $cityIndexExists = false;

    foreach ($indexes as $index) {
        if ($index->name === 'idx_venues_city') {
            $cityIndexExists = true;
            break;
        }
    }

    expect($cityIndexExists)->toBeTrue();
});

it('has name index', function (): void {
    $indexes = DB::select("PRAGMA index_list('venues')");
    $nameIndexExists = false;

    foreach ($indexes as $index) {
        if ($index->name === 'idx_venues_name') {
            $nameIndexExists = true;
            break;
        }
    }

    expect($nameIndexExists)->toBeTrue();
});

it('has composite city and name index', function (): void {
    $indexes = DB::select("PRAGMA index_list('venues')");
    $compositeIndexExists = false;

    foreach ($indexes as $index) {
        if ($index->name === 'idx_venues_city_name') {
            $compositeIndexExists = true;
            break;
        }
    }

    expect($compositeIndexExists)->toBeTrue();
});

it('has location index', function (): void {
    $indexes = DB::select("PRAGMA index_list('venues')");
    $locationIndexExists = false;

    foreach ($indexes as $index) {
        if ($index->name === 'idx_venues_location') {
            $locationIndexExists = true;
            break;
        }
    }

    expect($locationIndexExists)->toBeTrue();
});
