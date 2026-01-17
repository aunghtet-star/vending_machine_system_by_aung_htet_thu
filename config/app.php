<?php
/**
 * Application Configuration
 */

return [
    'name' => 'Vending Machine System',
    'version' => '1.0.0',
    'debug' => env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'UTC',
    
    // JWT Configuration
    'jwt' => [
        'secret' => env('JWT_SECRET', 'your-secret-key-change-in-production'),
        'algorithm' => 'HS256',
        'expiry' => 3600, // 1 hour
        'issuer' => 'vending-machine-api',
    ],
    
    // Pagination
    'pagination' => [
        'per_page' => 10,
        'max_per_page' => 100,
    ],
    
    // Session
    'session' => [
        'name' => 'vending_machine_session',
        'lifetime' => 7200, // 2 hours
    ],
];
