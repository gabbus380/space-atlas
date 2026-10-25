<?php

/*
| Sovereign Space ATLAS plans. Display prices are in US dollars, excluding taxes.
| Paddle charges the prices whose IDs are set below (create them with
| `php artisan atlas:paddle-setup`). Keep both in step.
*/
return [

    'prices' => [
        'personal' => ['year' => 228, 'month' => 29],
        'teams' => ['year' => 300, 'month' => 35],   // per user
        'api' => ['year' => 4990],
    ],

    'paddle_prices' => [
        'personal' => [
            'year' => env('PADDLE_PRICE_PERSONAL_YEAR'),
            'month' => env('PADDLE_PRICE_PERSONAL_MONTH'),
        ],
        'teams' => [
            'year' => env('PADDLE_PRICE_TEAMS_YEAR'),
            'month' => env('PADDLE_PRICE_TEAMS_MONTH'),
        ],
    ],

    'teams_min_seats' => 3,
    'teams_max_seats' => 100,

    // Free account: full country files a reader can open per calendar month (UTC)
    'free_files_per_month' => 3,

    // Paid plans: how many daily editions the archive keeps
    'archive_days' => 21,

    // Subscribers who join on or before this date keep their price while they stay subscribed
    'founding_until' => env('ATLAS_FOUNDING_UNTIL', '2027-03-31'),

    // Money-back guarantee on the first payment of any plan bought online
    'refund_days' => 14,

    'api' => [
        'starter_quota' => 10000,     // requests per day
        'starter_users' => 10,        // people who may see API data in the subscriber's tools
    ],

    'contact' => env('ATLAS_CONTACT', 'office@emmaconsulting.space'),

    // Where the atlas page and its data live (not public: the server filters them by plan)
    'html_path' => resource_path('atlas'),
    'data_path' => resource_path('atlas/data'),
    'audio_path' => resource_path('atlas/audio'),

    // KPI targets for the first year, shown on the admin page
    'kpi' => [
        'teams' => [3, 4],
        'personal' => [20, 30],
        'api' => [0, 0],
    ],
];
