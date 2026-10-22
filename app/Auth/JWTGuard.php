<?php

namespace App\Auth;

use App\Models\User;
use App\Services\JWTService;
use App\TokenType;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class JWTGuard implements Guard
{
    use GuardHelpers;

    private JWTService $jwtService;

    private Request $request;

    private bool $resolved = false;

    public function __construct(JWTService $jwtService, UserProvider $provider, Request $request) {
        $this->jwtService = $jwtService;
        $this->provider = $provider;
        $this->request = $request;
    }

    public function user(): ?Authenticatable
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->resolved) {
            return null;
        }

        $this->resolved = true;

        $bearerToken = $this->request->bearerToken();

        if (!is_string($bearerToken) || trim($bearerToken) === '') {
            return null;
        }

        $this->user = $this->resolveUserFromToken($bearerToken);
        return $this->user;
    }

    public function validate(array $credentials = []): bool
    {
        $token = $credentials['token'] ?? null;
        if (!is_string($token) || trim($token) == '') {
            return false;
        }
        return $this->resolveUserFromToken($token) !== null;
    }

    public function resolveUserFromToken(string $token): ?Authenticatable
    {
        try {
            $payload = $this->jwtService->validateToken($token, TokenType::ACCESS);
        } catch (\UnexpectedValueException | \InvalidArgumentException) {
            return null;
        }

        $user = $this->provider->retrieveById($payload->sub);
        if (!$user instanceof User) {
            return null;
        }

//        if (!$user->is_active) {
//            return null;
//        }

        return $user;
    }
}
