<?php
/**
 * CSRF Middleware
 * 
 * Protects against Cross-Site Request Forgery attacks.
 */

namespace App\Middleware;

use App\Core\Session;

class CsrfMiddleware
{
    /**
     * Handle the middleware
     */
    public function handle(): bool|array
    {
        // Only check on state-changing methods
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            
            if (!Session::verifyCsrfToken($token)) {
                http_response_code(419);
                if ($this->isAjaxRequest()) {
                    return ['error' => 'Page Expired (CSRF Token Mismatch)'];
                }
                die('Page Expired (CSRF Token Mismatch)');
            }
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