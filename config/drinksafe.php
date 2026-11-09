<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    |
    | report_spike_threshold: how many reports a venue can get in 24 hours
    | before the review queue flags it as a spike.
    |
    */

    'admin' => [
        'report_spike_threshold' => (int) env('DRINKSAFE_REPORT_SPIKE_THRESHOLD', 5),
    ],

];
