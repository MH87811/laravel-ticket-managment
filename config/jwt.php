<?php

return [
    'secret' => env('JWT_SECRET'),
    'algorithm' => env('JWT_ALGORITHM'),
    'access_ttl' => (int) env('JWT_ACCESS_TTL'),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL'),
    'issuer' => env('JWT_ISSUER', env('APP_URL')),
];
