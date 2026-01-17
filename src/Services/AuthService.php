<?php
/**
 * Authentication Service
 * 
 * Handles user authentication, sessions, and password management.
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

class AuthService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Attempt to authenticate a user
     */
    public function attempt(string $username, string $password): bool
    {
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE (username = :username OR email = :email) AND is_active = 1",
            ['username' => $username, 'email' => $username]
        );

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Update last login
        $this->db->update(
            'users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );

        // Store user in session
        $this->setSession($user);

        return true;
    }

    /**
     * Register a new user
     */
    public function register(array $data): int
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => 12]);

        return $this->db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'role' => $data['role'] ?? 'user',
            'balance' => $data['balance'] ?? 0.00,
        ]);
    }

    /**
     * Set user session
     */
    private function setSession(array $user): void
    {
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('email', $user['email']);
        Session::set('role', $user['role']);
        Session::set('balance', $user['balance']);
        Session::set('logged_in', true);
        Session::regenerate();
    }

    /**
     * Log out the current user
     */
    public function logout(): void
    {
        Session::destroy();
    }

    /**
     * Check if user is logged in
     */
    public function check(): bool
    {
        return Session::get('logged_in', false) === true;
    }

    /**
     * Get current user
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return $this->db->fetch(
            "SELECT id, username, email, role, balance, created_at, last_login FROM users WHERE id = :id",
            ['id' => Session::get('user_id')]
        );
    }

    /**
     * Get current user ID
     */
    public function id(): ?int
    {
        return Session::get('user_id');
    }

    /**
     * Get current user role
     */
    public function role(): ?string
    {
        return Session::get('role');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role() === 'admin';
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->role() === 'user';
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->role(), $roles, true);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => 12]);
        
        return $this->db->update(
            'users',
            ['password' => $hashedPassword],
            'id = :id',
            ['id' => $userId]
        ) > 0;
    }

    /**
     * Verify current password
     */
    public function verifyPassword(int $userId, string $password): bool
    {
        $user = $this->db->fetch(
            "SELECT password FROM users WHERE id = :id",
            ['id' => $userId]
        );

        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password']);
    }

    /**
     * Update user balance
     */
    public function updateBalance(int $userId, float $amount): bool
    {
        $result = $this->db->update(
            'users',
            ['balance' => $amount],
            'id = :id',
            ['id' => $userId]
        );

        // Update session balance if current user
        if ($userId === $this->id()) {
            Session::set('balance', $amount);
        }

        return $result > 0;
    }

    /**
     * Get user balance
     */
    public function getBalance(?int $userId = null): float
    {
        $userId = $userId ?? $this->id();
        
        if (!$userId) {
            return 0.0;
        }

        return (float) $this->db->fetchColumn(
            "SELECT balance FROM users WHERE id = :id",
            ['id' => $userId]
        );
    }

    /**
     * Check if username already exists
     */
    public function usernameExists(string $username): bool
    {
        $result = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM users WHERE username = :username",
            ['username' => $username]
        );
        return (int) $result > 0;
    }

    /**
     * Check if email already exists
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM users WHERE email = :email",
            ['email' => $email]
        );
        return (int) $result > 0;
    }
}
