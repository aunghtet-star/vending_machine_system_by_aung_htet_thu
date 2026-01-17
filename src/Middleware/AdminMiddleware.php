<?php
/**
 * Admin Middleware
 * 
 * Verifies that the user has admin role before allowing access.
 */

namespace App\Middleware;

use App\Core\Session;

class AdminMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(): bool|array
    {
        Session::start();

        // First check if logged in
        if (!Session::get('logged_in', false)) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                return ['error' => 'Unauthorized. Please log in.'];
            }
            
            header('Location: /login');
            exit;
        }

        // Then check if admin
        if (Session::get('role') !== 'admin') {
            if ($this->isAjaxRequest()) {
                http_response_code(403);
                return ['error' => 'Forbidden. Admin access required.'];
            }
            
            Session::flash('error', 'Access denied. Admin privileges required.');
            header('Location: /');
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
