<?php
/**
 * Session Manager
 * 
 * Handles PHP session management with security features.
 */

namespace App\Core;

class Session
{
    private static bool $started = false;

    /**
     * Start the session
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $config = require __DIR__ . '/../../config/app.php';
        $sessionConfig = $config['session'];

        // Set session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.gc_maxlifetime', $sessionConfig['lifetime']);

        session_name($sessionConfig['name']);
        session_start();

        self::$started = true;

        // Regenerate session ID periodically
        if (!isset($_SESSION['_last_regeneration'])) {
            self::regenerate();
        } elseif (time() - $_SESSION['_last_regeneration'] > 300) {
            self::regenerate();
        }

        // Initialize CSRF token if not present
        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Get CSRF token
     */
    public static function csrfToken(): string
    {
        self::start();
        return $_SESSION['_csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(?string $token): bool
    {
        self::start();
        return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], (string) $token);
    }


    /**
     * Regenerate session ID
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }

    /**
     * Get session value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set session value
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session key exists
     */
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session value
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Clear all session data
     */
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }

    /**
     * Destroy session
     */
    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        self::$started = false;
    }

    /**
     * Get all session data
     */
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }

    /**
     * Flash message helpers
     */
    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function hasFlash(string $key): bool
    {
        self::start();
        return isset($_SESSION['_flash'][$key]);
    }
}
