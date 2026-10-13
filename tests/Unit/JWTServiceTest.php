<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\JWTService;
use App\TokenType;
use Tests\TestCase;

class JWTServiceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_valid_generation_of_access_and_refresh_token_pair(): void
    {
        config([
            'jwt.secret' => bin2hex(random_bytes(36)),
            'jwt.algorithm' => 'HS256',
            'jwt.issuer' => 'http://localhost',
            'jwt.access_ttl' => 15,
            'jwt.refresh_ttl' => 10080,
        ]);

        $user = new User();
        $user->id = 123;

        $jwtService = new JWTService();
        $tokens = $jwtService->generateTokensPair($user);

        $this->assertArrayHasKey('access', $tokens);
        $this->assertArrayHasKey('refresh', $tokens);

        $this->assertNotSame($tokens['access'], $tokens['refresh']);

        $accessPayload = $jwtService->validateToken($tokens['access'], TokenType::ACCESS);
        $refreshPayload = $jwtService->validateToken($tokens['refresh'], TokenType::REFRESH);

        $this->assertSame('access', $accessPayload->type);
        $this->assertSame('refresh', $refreshPayload->type);

        $this->assertSame('123', $accessPayload->sub);
        $this->assertSame('123', $refreshPayload->sub);

        $this->assertSame('http://localhost', $accessPayload->iss);
        $this->assertSame('http://localhost', $refreshPayload->iss);

        $this->assertGreaterThan($accessPayload->iat, $accessPayload->exp);
        $this->assertGreaterThan($refreshPayload->iat, $refreshPayload->exp);
    }

    public function test_access_token_cannot_be_validated_at_refreshToken(): void
    {
        config([
            'jwt.secret' => bin2hex(random_bytes(36)),
            'jwt.algorithm' => 'HS256',
            'jwt.issuer' => 'http://localhost',
            'jwt.access_ttl' => 15,
            'jwt.refresh_ttl' => 10080,
        ]);

        $user = new User();
        $user->id = 123;
        $jwtService = new JWTService();

        $accessToken = $jwtService->generateTokensPair($user)['access'];

        $this->expectException(\UnexpectedValueException::class);

        $jwtService->validateToken($accessToken, TokenType::REFRESH);

    }

    public function test_tampered_token_is_rejected(): void
    {
        config([
            'jwt.secret' => bin2hex(random_bytes(36)),
            'jwt.algorithm' => 'HS256',
            'jwt.issuer' => 'http://localhost',
            'jwt.access_ttl' => 15,
            'jwt.refresh_ttl' => 10080,
        ]);

        $user = new User();
        $user->id = 123;
        $jwtService = new JWTService();

        $token = $jwtService->generateTokensPair($user)['access'];

        $parts = explode('.', $token);

        $this->assertCount(3, $parts);

        $payload = json_decode(
            \Firebase\JWT\JWT::urlsafeB64Decode($parts[1]),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $payload['sub'] = '999';

        $parts[1] = \Firebase\JWT\JWT::urlsafeB64Encode(json_encode($payload, JSON_THROW_ON_ERROR));

        $tampered_token = implode('.', $parts);

        $this->expectException(\UnexpectedValueException::class);

        $jwtService->validateToken($tampered_token, TokenType::ACCESS);
    }
}
