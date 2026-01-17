<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication: login, logout, registration.
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(?AuthService $authService = null)
    {
        $this->authService = $authService ?? new AuthService();
    }

    /**
     * Show login form
     * Route: GET /login
     */
    public function showLoginForm(): void
    {
        if ($this->authService->check()) {
            $redirect = $this->authService->isAdmin() ? '/products' : '/';
            $this->redirect($redirect);
            return;
        }

        $this->view('auth.login', [
            'title' => 'Login',
        ]);
    }

    /**
     * Process login
     * Route: POST /login
     */
    public function login(): void
    {
        $username = $this->input('username');
        $password = $this->input('password');

        // Validate input
        $errors = [];
        
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        }

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            
            Session::flash('errors', $errors);
            Session::flash('old', ['username' => $username]);
            $this->redirect('/login');
            return;
        }

        // Attempt authentication
        if ($this->authService->attempt($username, $password)) {
            $redirect = $this->authService->isAdmin() ? '/products' : '/';
            
            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => $redirect
                ]);
                return;
            }
            
            Session::flash('success', 'Welcome back!');
            $this->redirect($redirect);
            return;
        }

        // Authentication failed
        if ($this->isAjax()) {
            $this->json(['success' => false, 'error' => 'Invalid credentials'], 401);
            return;
        }
        
        Session::flash('error', 'Invalid username or password');
        Session::flash('old', ['username' => $username]);
        $this->redirect('/login');
    }

    /**
     * Show registration form
     * Route: GET /register
     */
    public function showRegisterForm(): void
    {
        if ($this->authService->check()) {
            $this->redirect('/');
            return;
        }

        $this->view('auth.register', [
            'title' => 'Register',
        ]);
    }

    /**
     * Process registration
     * Route: POST /register
     */
    public function register(): void
    {
        $data = $this->input();
        
        // Validate input
        $errors = $this->validateRegistration($data);

        if (!empty($errors)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'errors' => $errors], 422);
                return;
            }
            
            Session::flash('errors', $errors);
            Session::flash('old', [
                'username' => $data['username'] ?? '',
                'email' => $data['email'] ?? ''
            ]);
            $this->redirect('/register');
            return;
        }

        try {
            // Create user
            $userId = $this->authService->register([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'user',
                'balance' => 100.00, // Initial balance for new users
            ]);

            // Auto-login after registration
            $this->authService->attempt($data['username'], $data['password']);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Registration successful',
                    'redirect' => '/'
                ]);
                return;
            }
            
            Session::flash('success', 'Account created successfully! You have been given $100 starting balance.');
            $this->redirect('/');

        } catch (\Exception $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => 'Registration failed: ' . $e->getMessage()], 500);
                return;
            }
            
            // Show actual error in development
            $errorMessage = 'Registration failed. Please try again.';
            if (getenv('APP_DEBUG') === 'true' || true) {
                $errorMessage = 'Registration failed: ' . $e->getMessage();
            }
            
            Session::flash('error', $errorMessage);
            $this->redirect('/register');
        }
    }

    /**
     * Logout user
     * Route: POST /logout
     */
    public function logout(): void
    {
        $this->authService->logout();

        if ($this->isAjax()) {
            $this->json(['success' => true, 'message' => 'Logged out successfully']);
            return;
        }
        
        Session::flash('success', 'You have been logged out');
        $this->redirect('/login');
    }

    /**
     * Validate registration data
     */
    private function validateRegistration(array $data): array
    {
        $errors = [];

        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = 'Username must not exceed 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        } else {
            // Check if username already exists
            if ($this->authService->usernameExists($data['username'])) {
                $errors['username'] = 'This username is already taken';
            }
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        } else {
            // Check if email already exists
            if ($this->authService->emailExists($data['email'])) {
                $errors['email'] = 'This email is already registered';
            }
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        // Confirm password
        if (empty($data['password_confirmation'])) {
            $errors['password_confirmation'] = 'Please confirm your password';
        } elseif ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }

        return $errors;
    }
}
