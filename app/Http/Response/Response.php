<?php

namespace App\Http\Response;

use Illuminate\Http\JsonResponse;

class Response
{
    public function success(string $message, mixed $detail = null, int $status_code = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'detail' => $detail
        ], $status_code);
    }

    public function error(string $message, mixed $error = null, string $error_code = 'BAD_REQUEST', int $status_code = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $error_code,
            'error' => $error
        ], $status_code);
    }

    public function withCookie(
        JsonResponse $response,
        string $cookieName,
        string $cookieValue,
        int $cookieExpMinutes,
    ): JsonResponse
    {
        $cookie = cookie(
            name: $cookieName,
            value: $cookieValue,
            minutes: $cookieExpMinutes,
            path: '/',
            domain: null, // for dev mode
            secure: !config('app.debug'),
            httpOnly: true,
            raw: false,
            sameSite: 'Lax',
        );
        return $response->cookie($cookie);
    }
}
