<?php
/**
 * JWT Service
 * 
 * Handles JSON Web Token generation and validation for API authentication.
 */

namespace App\Services;

use App\Core\Database;

class JWTService
{
    private array $config;
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $appConfig = require __DIR__ . '/../../config/app.php';
        $this->config = $appConfig['jwt'];
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Generate a JWT token
     */
    public function generateToken(array $payload): string
    {
        $header = [
            'alg' => $this->config['algorithm'],
            'typ' => 'JWT'
        ];

        $payload = array_merge($payload, [
            'iss' => $this->config['issuer'],
            'iat' => time(),
            'exp' => time() + $this->config['expiry'],
            'jti' => bin2hex(random_bytes(16))
        ]);

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = $this->sign("{$headerEncoded}.{$payloadEncoded}");
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Validate and decode a JWT token
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $expectedSignature = $this->sign("{$headerEncoded}.{$payloadEncoded}");
        $actualSignature = $this->base64UrlDecode($signatureEncoded);

        if (!hash_equals($expectedSignature, $actualSignature)) {
            return null;
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        // Verify issuer
        if (isset($payload['iss']) && $payload['iss'] !== $this->config['issuer']) {
            return null;
        }

        // Check if token is revoked
        if ($this->isTokenRevoked($payload['jti'] ?? '')) {
            return null;
        }

        return $payload;
    }

    /**
     * Generate a refresh token
     */
    public function generateRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 7)); // 7 days

        $this->db->insert('api_tokens', [
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt
        ]);

        return $token;
    }

    /**
     * Validate refresh token and get user ID
     */
    public function validateRefreshToken(string $token): ?int
    {
        $tokenHash = hash('sha256', $token);

        $result = $this->db->fetch(
            "SELECT user_id, expires_at, is_revoked FROM api_tokens 
             WHERE token_hash = :hash AND is_revoked = 0",
            ['hash' => $tokenHash]
        );

        if (!$result) {
            return null;
        }

        if (strtotime($result['expires_at']) < time()) {
            return null;
        }

        return (int) $result['user_id'];
    }

    /**
     * Revoke a refresh token
     */
    public function revokeRefreshToken(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        
        return $this->db->update(
            'api_tokens',
            ['is_revoked' => 1],
            'token_hash = :hash',
            ['hash' => $tokenHash]
        ) > 0;
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): int
    {
        return $this->db->update(
            'api_tokens',
            ['is_revoked' => 1],
            'user_id = :user_id',
            ['user_id' => $userId]
        );
    }

    /**
     * Check if a token is revoked
     */
    private function isTokenRevoked(string $jti): bool
    {
        // For JWT tokens, we don't store them in database
        // This can be extended to support JWT blacklisting
        return false;
    }

    /**
     * Sign data with secret key
     */
    private function sign(string $data): string
    {
        return hash_hmac('sha256', $data, $this->config['secret'], true);
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Get token from Authorization header
     */
    public function getTokenFromHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
