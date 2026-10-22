<?php

return [
    'secret' => env('JWT_SECRET'),
    'algorithm' => env('JWT_ALGORITHM', 'HS256'),
    'access_ttl' => (int) env('JWT_ACCESS_TTL', 15),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 43200),
    'issuer' => env('JWT_ISSUER', env('APP_URL')),
];
