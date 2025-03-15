<?php

return [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('APP_URL') . '/auth/google/callback',
    'scopes' => [
        \Google\Service\Calendar::CALENDAR_EVENTS,
        \Google\Service\Calendar::CALENDAR_READONLY,
    ],
];
