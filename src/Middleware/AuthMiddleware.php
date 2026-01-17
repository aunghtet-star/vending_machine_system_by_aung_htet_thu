<?php
/**
 * Authentication Middleware
 * 
 * Verifies that the user is logged in before allowing access.
 */

namespace App\Middleware;

use App\Core\Session;

class AuthMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(): bool|array
    {
        Session::start();

        if (!Session::get('logged_in', false)) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                return ['error' => 'Unauthorized. Please log in.'];
            }
            
            header('Location: /login');
            exit;
        }

        return true;
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
