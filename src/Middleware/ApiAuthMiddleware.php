<?php
/**
 * API Authentication Middleware
 * 
 * Verifies JWT token for API requests.
 */

namespace App\Middleware;

use App\Core\Request;
use App\Services\TokenServiceInterface;

class ApiAuthMiddleware
{
    private TokenServiceInterface $tokenService;
    private Request $request;

    public function __construct(
        TokenServiceInterface $tokenService,
        Request $request
    ) {
        $this->tokenService = $tokenService;
        $this->request = $request;
    }

    /**
     * Handle the middleware
     */
    public function handle(): bool|array
    {
        $token = $this->tokenService->getTokenFromHeader();

        if (!$token) {
            http_response_code(401);
            return [
                'error' => 'Unauthorized',
                'message' => 'No token provided. Please include Bearer token in Authorization header.'
            ];
        }

        $payload = $this->tokenService->validateToken($token);

        if (!$payload) {
            http_response_code(401);
            return [
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token.'
            ];
        }

        // Store user info in Request object
        $this->request->setUser([
            'id' => $payload['user_id'],
            'username' => $payload['username'],
            'role' => $payload['role']
        ]);

        return true;
    }
}
