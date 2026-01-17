<?php
/**
 * API Admin Middleware
 * 
 * Verifies that the API user has admin role.
 */

namespace App\Middleware;

class ApiAdminMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(): bool|array
    {
        // First run API auth middleware
        $apiAuth = new ApiAuthMiddleware();
        $result = $apiAuth->handle();

        if ($result !== true) {
            return $result;
        }

        // Check if admin
        if (!isset($GLOBALS['api_user']) || $GLOBALS['api_user']['role'] !== 'admin') {
            http_response_code(403);
            return [
                'error' => 'Forbidden',
                'message' => 'Admin access required.'
            ];
        }

        return true;
    }
}
