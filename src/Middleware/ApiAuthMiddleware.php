<?php
/**
 * API Authentication Middleware
 * 
 * Verifies JWT token for API requests.
 */

namespace App\Middleware;

use App\Services\JWTService;

class ApiAuthMiddleware
{
    private JWTService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JWTService();
    }

    /**
     * Handle the middleware
     */
    public function handle(): bool|array
    {
        $token = $this->jwtService->getTokenFromHeader();

        if (!$token) {
            http_response_code(401);
            return [
                'error' => 'Unauthorized',
                'message' => 'No token provided. Please include Bearer token in Authorization header.'
            ];
        }

        $payload = $this->jwtService->validateToken($token);

        if (!$payload) {
            http_response_code(401);
            return [
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token.'
            ];
        }

        // Store user info in global for access in controllers
        $GLOBALS['api_user'] = [
            'id' => $payload['user_id'],
            'username' => $payload['username'],
            'role' => $payload['role']
        ];

        return true;
    }
}
