<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateServiceToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token || ! hash_equals(config('auth.service_token'), $token)) {
            return response()->json([
                'message' => __('referral.messages.unauthorized'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
