<?php

namespace App\Http\Responses;

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

    public function created(string $objectName, mixed $detail): JsonResponse
    {
        return $this->success(
            message: "$objectName Created Successfully",
            detail: $detail,
            status_code: 201,
        );
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

    public function unauthorizedError(?string $reason = null, ?string $action = null): JsonResponse
    {
        return $this->error(
            message: 'Authentication Required',
            error: !$reason ? ('Authentication Required' . ($action ? "To {$action}" : '.')) : $reason,
            error_code: 'UNAUTHORIZED',
            status_code: 401,
        );
    }

    public function permissionDeniedError(string $action, ?string $reason = null): JsonResponse
    {
        return $this->error(
            message: 'Permission Denied',
            error: $reason ?? 'Permission Denied For' . ($action ? "To {$action}" : '.'),
            error_code: 'FORBIDDEN',
            status_code: 403,
        );
    }

    public function notFoundError(string $objectName): JsonResponse
    {
        return $this->error(
            message: 'could not find requested object',
            error: "$objectName Not Found",
            error_code: 'NOT_FOUND',
            status_code: 404,
        );
    }

    public function internalServerError(string $error): JsonResponse
    {
        return $this->error(
            message: 'Something went wrong',
            error: $error,
            error_code: 'INTERNAL_SERVER_ERROR',
            status_code: 500,
        );
    }
}
