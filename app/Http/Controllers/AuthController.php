<?php

namespace App\Http\Controllers;

use App\Http\Responses\Response as ApiResponse;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    function __construct(private ApiResponse $response, private JWTService $jwtService) {    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'username' => 'string|required|unique:users,username',
            'email' => 'email|required|unique:users,email',
            'phone' => 'string|required|unique:users,phone|regex:/^09[0-9]{9}$/',
            'password' => 'string|required|confirmed',

            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'bio' => 'required|string',
            'avatar' => 'required|string|max:255',
        ]);

        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ];

        $profileData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'bio' => $data['bio'],
            'avatar' => $data['avatar'],
        ];

        [$user, $tokens] = DB::transaction(function () use ($request, $userData, $profileData) {
            $user = User::create($userData);
            $user->profile()->create($profileData);

            $tokens = $this->jwtService->generateTokensPair($user);
            $this->jwtService->saveRefreshToken(
                token: $tokens['refresh'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
            return [$user, $tokens];
        });

        $response = $this->response->created(
            objectName: class_basename(User::class),
            detail: $user->load('profile'),
        );

        return $this->response->withCookie(
            response: $response,
            cookieName: 'refresh_token',
            cookieValue: $tokens['refresh'],
            cookieExpMinutes: (int) config('jwt.refresh_ttl', 43200),
        );
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'identifier' => 'required|string',
            'password' => 'string|required'
        ]);

        $identifier = $credentials['identifier'];
        $user = User::query()->where(function ($query) use ($identifier) {
            $query
                ->where('users.username', $identifier)
                ->orWhere('users.phone', $identifier)
                ->orWhere('users.email', $identifier);
        })->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return $this->response->error(
                message: 'Authentication Failure',
                error: 'Invalid Credentials',
                error_code: 'AUTHENTICATION_FAILED',
                status_code: 401,
            );
        }
        $tokens = DB::transaction(
            function () use ($user, $request) {
                $tokens = $this->jwtService->generateTokensPair($user);
                $this->jwtService->saveRefreshToken(
                    token: $tokens['refresh'],
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                );
                return $tokens;
            }
        );

        $response = $this->response->success(
            message: 'Login Successful',
            detail: [
                'access' => $tokens['access'],
                'user' => $user->load('profile'),
            ],
        );
        return $this->response->withCookie(
            response: $response,
            cookieName: 'refresh_token',
            cookieValue: $tokens['refresh'],
            cookieExpMinutes: (int) config('jwt.refresh_ttl', 43200),
        );
    }

    public function me(Request $request)
    {
        return $this->response->success(
            message: 'Success',
            detail: ['id' => $request->user('api')->getAuthIdentifier()],
            status_code: 202
        );
    }
}
