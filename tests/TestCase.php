<?php
/**
 * Base Test Case
 * 
 * Provides common functionality for all tests.
 */

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset session
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
    }

    /**
     * Create a mock database connection
     */
    protected function createMockDatabase(): \PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(\App\Core\Database::class);
    }

    /**
     * Set request method
     */
    protected function setRequestMethod(string $method): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
    }

    /**
     * Set POST data
     */
    protected function setPostData(array $data): void
    {
        $_POST = $data;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    /**
     * Set GET data
     */
    protected function setGetData(array $data): void
    {
        $_GET = $data;
    }

    /**
     * Set session data
     */
    protected function setSessionData(array $data): void
    {
        $_SESSION = array_merge($_SESSION, $data);
    }

    /**
     * Login as user
     */
    protected function loginAs(array $user): void
    {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'] ?? 1;
        $_SESSION['username'] = $user['username'] ?? 'testuser';
        $_SESSION['email'] = $user['email'] ?? 'test@example.com';
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['balance'] = $user['balance'] ?? 100.00;
    }

    /**
     * Login as admin
     */
    protected function loginAsAdmin(): void
    {
        $this->loginAs([
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'balance' => 1000.00
        ]);
    }
}
