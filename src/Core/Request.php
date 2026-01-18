<?php
/**
 * Request Class
 * 
 * Encapsulates HTTP request information.
 */

namespace App\Core;

class Request
{
    private array $attributes = [];
    private ?array $user = null;
    private array $input;

    public function __construct()
    {
        // Parse input on initialization
        $this->input = array_merge($_GET, $_POST);
        
        // Also check JSON body
        $jsonBody = json_decode(file_get_contents('php://input'), true);
        if (is_array($jsonBody)) {
            $this->input = array_merge($this->input, $jsonBody);
        }
    }

    /**
     * Get request method
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get request URI
     */
    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * Get input value
     */
    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->input;
        }

        return $this->input[$key] ?? $default;
    }

    /**
     * Set the authenticated user
     */
    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the authenticated user
     */
    public function user(): ?array
    {
        return $this->user;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
