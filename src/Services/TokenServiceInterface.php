<?php

namespace App\Services;

interface TokenServiceInterface
{
    public function generateToken(array $payload): string;
    public function validateToken(string $token): ?array;
    public function generateRefreshToken(int $userId): string;
    public function validateRefreshToken(string $token): ?int;
    public function revokeRefreshToken(string $token): bool;
    public function revokeAllUserTokens(int $userId): int;
    public function getTokenFromHeader(): ?string;
}
