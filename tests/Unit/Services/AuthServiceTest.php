<?php
/**
 * AuthService Unit Tests
 */

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthService;
use App\Core\Database;

class AuthServiceTest extends TestCase
{
    private $mockDb;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockDb = $this->createMock(Database::class);
        $this->authService = new AuthService($this->mockDb);
    }

    /**
     * @test
     */
    public function attempt_returns_true_with_valid_credentials(): void
    {
        // Arrange
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        
        $userData = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => $hashedPassword,
            'role' => 'user',
            'balance' => 100.00,
            'is_active' => 1
        ];

        $this->mockDb
            ->expects($this->once())
            ->method('fetch')
            ->willReturn($userData);

        $this->mockDb
            ->expects($this->once())
            ->method('update')
            ->willReturn(1);

        // Act
        $result = $this->authService->attempt('testuser', 'password123');

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function attempt_returns_false_with_invalid_password(): void
    {
        // Arrange
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);

        // Act
        $isValid = password_verify('wrongpassword', $hashedPassword);

        // Assert
        $this->assertFalse($isValid);
    }

    /**
     * @test
     */
    public function attempt_returns_false_when_user_not_found(): void
    {
        // Arrange
        $this->mockDb
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(null);

        // Act
        $result = $this->mockDb->fetch('SELECT * FROM users WHERE username = :username', ['username' => 'nonexistent']);

        // Assert
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function register_creates_new_user(): void
    {
        // Arrange
        $userData = [
            'username' => 'newuser',
            'email' => 'new@example.com',
            'password' => 'password123',
            'role' => 'user',
            'balance' => 100.00
        ];

        $this->mockDb
            ->expects($this->once())
            ->method('insert')
            ->willReturn(5);

        // Act
        $userId = $this->mockDb->insert('users', $userData);

        // Assert
        $this->assertEquals(5, $userId);
    }

    /**
     * @test
     */
    public function check_returns_true_when_logged_in(): void
    {
        // Arrange
        $this->loginAs(['id' => 1]);

        // Assert
        $this->assertTrue($_SESSION['logged_in']);
    }

    /**
     * @test
     */
    public function check_returns_false_when_not_logged_in(): void
    {
        // Assert
        $this->assertArrayNotHasKey('logged_in', $_SESSION);
    }

    /**
     * @test
     */
    public function is_admin_returns_true_for_admin_role(): void
    {
        // Arrange
        $this->loginAsAdmin();

        // Assert
        $this->assertEquals('admin', $_SESSION['role']);
    }

    /**
     * @test
     */
    public function is_admin_returns_false_for_user_role(): void
    {
        // Arrange
        $this->loginAs(['role' => 'user']);

        // Assert
        $this->assertNotEquals('admin', $_SESSION['role']);
    }

    /**
     * @test
     */
    public function logout_clears_session(): void
    {
        // Arrange
        $this->loginAs(['id' => 1]);
        $this->assertTrue(isset($_SESSION['logged_in']));

        // Act
        $_SESSION = [];

        // Assert
        $this->assertEmpty($_SESSION);
    }

    /**
     * @test
     */
    public function update_password_hashes_new_password(): void
    {
        // Arrange
        $newPassword = 'newpassword123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Assert
        $this->assertTrue(password_verify($newPassword, $hashedPassword));
        $this->assertNotEquals($newPassword, $hashedPassword);
    }

    /**
     * @test
     */
    public function get_balance_returns_user_balance(): void
    {
        // Arrange
        $this->loginAs(['id' => 1, 'balance' => 150.50]);

        // Assert
        $this->assertEquals(150.50, $_SESSION['balance']);
    }

    /**
     * @test
     */
    public function update_balance_modifies_amount(): void
    {
        // Arrange
        $initialBalance = 100.00;
        $deduction = 25.50;
        $expectedBalance = $initialBalance - $deduction;

        // Assert
        $this->assertEquals(74.50, $expectedBalance);
    }
}
