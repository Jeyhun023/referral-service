<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const HEADER = 'Idempotency-Key';
    private const TTL_HOURS = 24;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $idempotencyKey = $request->header(self::HEADER);

        if ($idempotencyKey === null) {
            return $next($request);
        }

        $existing = IdempotencyKey::where('key', $idempotencyKey)->first();

        if ($existing && ! $existing->isExpired()) {
            return new JsonResponse(
                data: $existing->response_body,
                status: $existing->status_code,
                headers: array_merge(
                    $existing->response_headers ?? [],
                    ['X-Idempotency-Replayed' => 'true'],
                ),
            );
        }

        if ($existing?->isExpired()) {
            $existing->delete();
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->isSuccessful() || $response->isClientError()) {
            IdempotencyKey::create([
                'key' => $idempotencyKey,
                'status_code' => $response->getStatusCode(),
                'response_body' => json_decode($response->getContent(), true),
                'response_headers' => ['Content-Type' => $response->headers->get('Content-Type')],
                'expires_at' => now()->addHours(self::TTL_HOURS),
            ]);
        }

        return $response;
    }
}
