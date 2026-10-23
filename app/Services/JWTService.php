<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use App\TokenType;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use UnexpectedValueException;
use RuntimeException;
use Carbon\Carbon;

class JWTService
{
    private function secret(): string
    {
        $secret = config('jwt.secret');
        if (!is_string($secret) || rtrim($secret) === '') {
            throw new RuntimeException('JWT_SECRET is not configured');
        }
        return $secret;
    }

    private function getTtlForType(TokenType $type): int
    {
        return match ($type) {
            TokenType::ACCESS => (int) config('jwt.access_ttl', 15),
            TokenType::REFRESH => (int) config('jwt.refresh_ttl', 43200)
        };
    }

    private function generateToken(User $user, TokenType $tokenType): string
    {
        $now = time();

        $payload = [
            'iss' => config('jwt.issuer'),
            'iat' => $now,
            'exp' => (int) $now + ($this->getTtlForType($tokenType) * 60),
            'sub' => (string) $user->getKey(),
            'jti' => bin2hex(random_bytes(16)),
            'type' => $tokenType->value,
        ];

        return JWT::encode(
            $payload,
            $this->secret(),
            config('jwt.algorithm', 'HS256')
        );
    }

    public function generateTokensPair(User $user): array
    {
        $accessToken = $this->generateToken($user, TokenType::ACCESS);
        $refreshToken = $this->generateToken($user, TokenType::REFRESH);
        return [
            'access' => $accessToken,
            'refresh' => $refreshToken,
        ];
    }

    public function validateToken(string $token, TokenType $tokenType): \stdClass
    {
        $key = new Key($this->secret(), config('jwt.algorithm', 'HS256'));

        $parsedToken = JWT::decode($token, $key);

        if (
            !isset($parsedToken->type) ||
            $parsedToken->type !== $tokenType->value
        ) {
            throw new UnexpectedValueException('Invalid token type.');
        }

        if (
            !isset($parsedToken->sub) ||
            !is_string($parsedToken->sub) ||
            trim($parsedToken->sub) === ''
        ) {
            throw new UnexpectedValueException('Invalid token subject.');
        }

        if (
            !isset($parsedToken->iss) ||
            $parsedToken->iss !== config('jwt.issuer')
        ) {
            throw new UnexpectedValueException('Invalid token issuer.');
        }

        if (
            !isset($parsedToken->jti) ||
            !is_string($parsedToken->jti) ||
            trim($parsedToken->jti) === ''
        ) {
            throw new UnexpectedValueException('Invalid token ID.');
        }

        return $parsedToken;
    }

    public function saveRefreshToken(string $token, ?string $ipAddress = null, ?string $userAgent = null)
    {
        $payload = $this->validateToken($token, TokenType::REFRESH);

        return RefreshToken::create([
            'user_id' => $payload->sub,
            'refresh_token_hash' => hash('sha256', $token),
            'jti' => $payload->jti,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => Carbon::createFromTimestampUTC($payload->exp),
        ]);
    }

    public function getTokenFromDB(string $token, bool $lockForUpdate = false): ?RefreshToken
    {
        $payload = $this->validateToken($token, TokenType::REFRESH);

        $query = RefreshToken::query()
            ->where('user_id', $payload->sub)
            ->where('jti', $payload->jti)
            ->where('refresh_token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function revokeToken(string $token): void
    {
        $refreshToken = $this->getTokenFromDB($token);
        if (!$refreshToken) {
            throw new UnexpectedValueException('Token does not exists.');
        };
        $refreshToken->updateOrFail([
            'revoked_at' => Carbon::now('UTC')
        ]);
    }
}
