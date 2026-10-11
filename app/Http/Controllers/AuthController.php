<?php

namespace App\Http\Controllers;

use App\Http\Response\Response;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    function __construct(private Response $response, private JWTService $jwtService) {    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => 'string|required|unique:users,username',
            'email' => 'email|required|unique:users,email',
            'phone' => 'string|required|unique:users,phone|regex:/^09[0-9]{9}$/',
            'password' => 'string|required|confirmed',
        ]);

        [$user, $tokens] = DB::transaction(function () use ($request, $data) {
            $user = User::create($data);

            $tokens = $this->jwtService->generateTokensPair($user);
            $this->jwtService->saveRefreshToken(
                token: $tokens['refresh'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
            return [$user, $tokens];
        });

        $response = $this->response->success(
            message: 'Registration Successful!',
            detail: [
                'accessToken' => $tokens['access'],
                'user' => $user,
            ]
        );

        return $this->response->withCookie(
            response: $response,
            cookieName: 'refresh_token',
            cookieValue: $tokens['refresh'],
            cookieExpMinutes: config('jwt.refresh_ttl'),
        );
    }
}
