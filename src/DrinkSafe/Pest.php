<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(__DIR__.'/Venues/Tests/Unit', __DIR__.'/Venues/Tests/Feature')
    ->in(__DIR__.'/Reports/Tests/Unit', __DIR__.'/Reports/Tests/Feature')
    ->in(__DIR__.'/Shared/Tests/Unit', __DIR__.'/Shared/Tests/Feature')
    ->in(__DIR__.'/Heatmap/Tests/Unit', __DIR__.'/Heatmap/Tests/Feature');
