<?php
/**
 * API Authentication Controller
 * 
 * Handles JWT authentication for the API.
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Services\JWTService;
use App\Services\TokenServiceInterface;

class AuthApiController extends Controller
{
    private TokenServiceInterface $jwtService;
    private User $userModel;
    private Database $db;

    public function __construct(
        ?TokenServiceInterface $jwtService = null,
        ?User $userModel = null,
        ?Database $db = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->jwtService = $jwtService ?? new JWTService($this->db);
        $this->userModel = $userModel ?? new User($this->db);

        header('Content-Type: application/json');
    }

    /**
     * Login and get JWT token
     * POST /api/auth/login
     */
    public function login(): void
    {
        $username = $this->input('username');
        $password = $this->input('password');

        // Validate input
        if (empty($username) || empty($password)) {
            $this->json([
                'success' => false,
                'error' => 'Username and password are required'
            ], 422);
            return;
        }

        // Find user
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $user = $this->userModel->findByEmail($username);
        }

        if (!$user || !password_verify($password, $user->password)) {
            $this->json([
                'success' => false,
                'error' => 'Invalid credentials'
            ], 401);
            return;
        }

        if (!$user->isActive) {
            $this->json([
                'success' => false,
                'error' => 'Account is disabled'
            ], 403);
            return;
        }

        // Generate tokens
        $accessToken = $this->jwtService->generateToken([
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
        ]);

        $refreshToken = $this->jwtService->generateRefreshToken($user->id);

        // Update last login
        $this->db->update(
            'users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user->id]
        );

        $this->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'balance' => $user->balance,
                ]
            ]
        ]);
    }

    /**
     * Register a new user
     * POST /api/auth/register
     */
    public function register(): void
    {
        $data = $this->input();
        
        // Validate input
        $errors = $this->validateRegistration($data);

        if (!empty($errors)) {
            $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 422);
            return;
        }

        // Check if username exists
        if ($this->userModel->findByUsername($data['username'])) {
            $this->json([
                'success' => false,
                'errors' => ['username' => 'Username already taken']
            ], 422);
            return;
        }

        // Check if email exists
        if ($this->userModel->findByEmail($data['email'])) {
            $this->json([
                'success' => false,
                'errors' => ['email' => 'Email already registered']
            ], 422);
            return;
        }

        try {
            // Create user
            $user = $this->userModel->create([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'user',
                'balance' => 100.00, // Initial balance
            ]);

            // Generate tokens
            $accessToken = $this->jwtService->generateToken([
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ]);

            $refreshToken = $this->jwtService->generateRefreshToken($user->id);

            $this->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                    'user' => $user->toArray()
                ]
            ], 201);

        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Registration failed'
            ], 500);
        }
    }

    /**
     * Refresh access token
     * POST /api/auth/refresh
     */
    public function refresh(): void
    {
        $refreshToken = $this->input('refresh_token');

        if (empty($refreshToken)) {
            $this->json([
                'success' => false,
                'error' => 'Refresh token is required'
            ], 422);
            return;
        }

        $userId = $this->jwtService->validateRefreshToken($refreshToken);

        if (!$userId) {
            $this->json([
                'success' => false,
                'error' => 'Invalid or expired refresh token'
            ], 401);
            return;
        }

        $user = $this->userModel->find($userId);

        if (!$user || !$user->isActive) {
            $this->json([
                'success' => false,
                'error' => 'User not found or disabled'
            ], 401);
            return;
        }

        // Revoke old refresh token
        $this->jwtService->revokeRefreshToken($refreshToken);

        // Generate new tokens
        $accessToken = $this->jwtService->generateToken([
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
        ]);

        $newRefreshToken = $this->jwtService->generateRefreshToken($user->id);

        $this->json([
            'success' => true,
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]
        ]);
    }

    /**
     * Logout (revoke tokens)
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        $refreshToken = $this->input('refresh_token');

        if ($refreshToken) {
            $this->jwtService->revokeRefreshToken($refreshToken);
        }

        $this->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current user info
     * GET /api/auth/me
     */
    public function me(): void
    {
        if (!isset($GLOBALS['api_user'])) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized'
            ], 401);
            return;
        }

        $user = $this->userModel->find($GLOBALS['api_user']['id']);

        if (!$user) {
            $this->json([
                'success' => false,
                'error' => 'User not found'
            ], 404);
            return;
        }

        $this->json([
            'success' => true,
            'data' => $user->toArray()
        ]);
    }

    /**
     * Validate registration data
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        return $errors;
    }
}
