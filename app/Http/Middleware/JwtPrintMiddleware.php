<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JwtPrintMiddleware
{
    public function handle($request, Closure $next)
    {
        // Support token from query string (?token=) for window.open() calls,
        // or from Authorization header for AJAX calls.
        $token = $request->query('token') ?? $request->bearerToken();

        if (empty($token)) {
            abort(401, 'Token tidak ditemukan.');
        }

        try {
            JWTAuth::setToken($token)->authenticate();
        } catch (TokenInvalidException $e) {
            abort(401, 'Token tidak valid.');
        } catch (TokenExpiredException $e) {
            abort(401, 'Token sudah kadaluarsa.');
        } catch (Exception $e) {
            abort(401, 'Autentikasi gagal.');
        }

        return $next($request);
    }
}
