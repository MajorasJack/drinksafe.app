<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('has reports table', function (): void {
    expect(Schema::hasTable('reports'))->toBeTrue();
});

it('has correct columns in reports table', function (): void {
    $columns = [
        'uuid',
        'venue_uuid',
        'incident_date',
        'time_of_day',
        'description',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    foreach ($columns as $column) {
        expect(Schema::hasColumn('reports', $column))->toBeTrue();
    }
});

it('has soft deletes column', function (): void {
    expect(Schema::hasColumn('reports', 'deleted_at'))->toBeTrue();
});

it('has foreign key to venues table', function (): void {
    $foreignKeys = DB::select("PRAGMA foreign_key_list('reports')");
    $venueUuidForeignKeyExists = false;

    foreach ($foreignKeys as $foreignKey) {
        if ($foreignKey->table === 'venues' && $foreignKey->from === 'venue_uuid') {
            $venueUuidForeignKeyExists = true;
            break;
        }
    }

    expect($venueUuidForeignKeyExists)->toBeTrue();
});

it('has venue_uuid and incident_date index', function (): void {
    $indexes = DB::select("PRAGMA index_list('reports')");
    $compositeIndexExists = false;

    foreach ($indexes as $index) {
        if ($index->name === 'idx_reports_venue_date') {
            $compositeIndexExists = true;
            break;
        }
    }

    expect($compositeIndexExists)->toBeTrue();
});

it('has deleted_at and created_at index', function (): void {
    $indexes = DB::select("PRAGMA index_list('reports')");
    $deletedCreatedIndexExists = false;

    foreach ($indexes as $index) {
        if ($index->name === 'idx_reports_deleted_created') {
            $deletedCreatedIndexExists = true;
            break;
        }
    }

    expect($deletedCreatedIndexExists)->toBeTrue();
});

it('has time_of_day index', function (): void {
    $indexes = DB::select("PRAGMA index_list('reports')");
    $timeOfDayIndexExists = false;

    foreach ($indexes as $index) {
        if ($index->name === 'idx_reports_time_of_day') {
            $timeOfDayIndexExists = true;
            break;
        }
    }

    expect($timeOfDayIndexExists)->toBeTrue();
});

it('enforces cascade delete when venue is deleted', function (): void {
    $venueUuid = Str::uuid()->toString();
    DB::table('venues')->insert([
        'uuid' => $venueUuid,
        'name' => 'Test Venue',
        'slug' => 'test-venue',
        'city' => 'Test City',
        'address' => 'Test Address',
        'latitude' => 51.5074,
        'longitude' => -0.1278,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Arrange: Create a report for this venue
    $reportUuid = Str::uuid()->toString();
    DB::table('reports')->insert([
        'uuid' => $reportUuid,
        'venue_uuid' => $venueUuid,
        'incident_date' => now()->toDateString(),
        'time_of_day' => 'Evening',
        'description' => 'Test report description',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Assert: Report exists
    $reportExists = DB::table('reports')->where('uuid', $reportUuid)->exists();
    expect($reportExists)->toBeTrue();

    // Act: Delete the venue
    DB::table('venues')->where('uuid', $venueUuid)->delete();

    // Assert: Report should be cascade deleted
    $reportStillExists = DB::table('reports')->where('uuid', $reportUuid)->exists();
    expect($reportStillExists)->toBeFalse();
});
