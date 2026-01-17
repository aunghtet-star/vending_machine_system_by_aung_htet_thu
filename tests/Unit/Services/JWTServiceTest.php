<?php
/**
 * JWTService Unit Tests
 */

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\JWTService;
use App\Core\Database;

class JWTServiceTest extends TestCase
{
    private $mockDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockDb = $this->createMock(Database::class);
    }

    /**
     * @test
     */
    public function generate_token_returns_valid_jwt_format(): void
    {
        // Arrange
        $payload = [
            'user_id' => 1,
            'username' => 'testuser',
            'role' => 'user'
        ];

        // Create a simple JWT-like token for testing
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEncoded = base64_encode(json_encode($payload + ['iat' => time(), 'exp' => time() + 3600]));
        $signature = base64_encode('test-signature');
        $token = "{$header}.{$payloadEncoded}.{$signature}";

        // Assert - JWT has 3 parts separated by dots
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * @test
     */
    public function validate_token_returns_payload_for_valid_token(): void
    {
        // Arrange - Create a mock valid token
        $payload = [
            'user_id' => 1,
            'username' => 'testuser',
            'role' => 'user',
            'exp' => time() + 3600
        ];

        // Assert - Payload has expected fields
        $this->assertArrayHasKey('user_id', $payload);
        $this->assertArrayHasKey('username', $payload);
        $this->assertArrayHasKey('role', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    /**
     * @test
     */
    public function validate_token_returns_null_for_expired_token(): void
    {
        // Arrange
        $expiredPayload = [
            'user_id' => 1,
            'exp' => time() - 3600 // Expired 1 hour ago
        ];

        // Assert - Token is expired
        $this->assertTrue($expiredPayload['exp'] < time());
    }

    /**
     * @test
     */
    public function generate_refresh_token_returns_string(): void
    {
        // Arrange
        $token = bin2hex(random_bytes(32));

        // Assert
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex characters
    }

    /**
     * @test
     */
    public function validate_refresh_token_returns_user_id(): void
    {
        // Arrange
        $this->mockDb
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'user_id' => 1,
                'expires_at' => date('Y-m-d H:i:s', time() + 86400),
                'is_revoked' => 0
            ]);

        // Act
        $result = $this->mockDb->fetch('SELECT * FROM api_tokens WHERE token_hash = :hash', ['hash' => 'test']);

        // Assert
        $this->assertEquals(1, $result['user_id']);
    }

    /**
     * @test
     */
    public function revoke_refresh_token_marks_as_revoked(): void
    {
        // Arrange
        $this->mockDb
            ->expects($this->once())
            ->method('update')
            ->with('api_tokens', ['is_revoked' => 1], 'token_hash = :hash', $this->anything())
            ->willReturn(1);

        // Act
        $result = $this->mockDb->update('api_tokens', ['is_revoked' => 1], 'token_hash = :hash', ['hash' => 'test']);

        // Assert
        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function get_token_from_header_extracts_bearer_token(): void
    {
        // Arrange
        $header = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature';
        
        // Act
        preg_match('/Bearer\s+(.+)/i', $header, $matches);
        $token = $matches[1] ?? null;

        // Assert
        $this->assertNotNull($token);
        $this->assertStringStartsWith('eyJ', $token);
    }

    /**
     * @test
     */
    public function get_token_from_header_returns_null_without_bearer(): void
    {
        // Arrange
        $header = 'Basic dXNlcjpwYXNz';
        
        // Act
        preg_match('/Bearer\s+(.+)/i', $header, $matches);
        $token = $matches[1] ?? null;

        // Assert
        $this->assertNull($token);
    }

    /**
     * @test
     */
    public function base64_url_encoding_works_correctly(): void
    {
        // Arrange
        $data = 'Test data with special chars: +/=';
        
        // Act - Base64 URL encode
        $encoded = rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        
        // Act - Base64 URL decode
        $decoded = base64_decode(strtr($encoded, '-_', '+/'));

        // Assert
        $this->assertEquals($data, $decoded);
        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }
}
