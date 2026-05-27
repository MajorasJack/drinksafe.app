<?php

declare(strict_types=1);

use DrinkSafe\Shared\Services\GeolocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('GeolocationService', function (): void {
    beforeEach(function (): void {
        $this->service = new GeolocationService;
    });

    describe('distance', function (): void {
        it('calculates distance between London and Manchester correctly', function (): void {
            // Arrange
            $londonLat = 51.5074;
            $londonLng = -0.1278;
            $manchesterLat = 53.4808;
            $manchesterLng = -2.2426;

            // Act
            $distance = $this->service->distance($londonLat, $londonLng, $manchesterLat, $manchesterLng);

            // Assert
            expect($distance)->toBeGreaterThan(250.0)
                ->and($distance)->toBeLessThan(270.0);
        });

        it('returns zero for same coordinates', function (): void {
            // Arrange
            $lat = 51.5074;
            $lng = -0.1278;

            // Act
            $distance = $this->service->distance($lat, $lng, $lat, $lng);

            // Assert
            expect($distance)->toBe(0.0);
        });
    });

    describe('isWithinRadius', function (): void {
        it('returns true for points within radius', function (): void {
            // Arrange
            $londonLat = 51.5074;
            $londonLng = -0.1278;
            $nearbyLat = 51.5155;
            $nearbyLng = -0.1415;
            $radius = 5;

            // Act
            $result = $this->service->isWithinRadius($londonLat, $londonLng, $nearbyLat, $nearbyLng, $radius);

            // Assert
            expect($result)->toBeTrue();
        });

        it('returns false for points outside radius', function (): void {
            // Arrange
            $londonLat = 51.5074;
            $londonLng = -0.1278;
            $manchesterLat = 53.4808;
            $manchesterLng = -2.2426;
            $radius = 100;

            // Act
            $result = $this->service->isWithinRadius($londonLat, $londonLng, $manchesterLat, $manchesterLng, $radius);

            // Assert
            expect($result)->toBeFalse();
        });
    });

    describe('getBoundingBox', function (): void {
        it('returns valid coordinates', function (): void {
            // Arrange
            $lat = 51.5074;
            $lng = -0.1278;
            $radius = 10;

            // Act
            $box = $this->service->getBoundingBox($lat, $lng, $radius);

            // Assert
            expect($box)->toHaveKeys(['minLat', 'maxLat', 'minLng', 'maxLng'])
                ->and($box['minLat'])->toBeLessThan($lat)
                ->and($box['maxLat'])->toBeGreaterThan($lat)
                ->and($box['minLng'])->toBeLessThan($lng)
                ->and($box['maxLng'])->toBeGreaterThan($lng);
        });

        it('creates box larger than original point', function (): void {
            // Arrange
            $lat = 51.5074;
            $lng = -0.1278;
            $radius = 20;

            // Act
            $box = $this->service->getBoundingBox($lat, $lng, $radius);

            // Assert
            $latRange = $box['maxLat'] - $box['minLat'];
            $lngRange = $box['maxLng'] - $box['minLng'];

            expect($latRange)->toBeGreaterThan(0)
                ->and($lngRange)->toBeGreaterThan(0);
        });
    });
});
