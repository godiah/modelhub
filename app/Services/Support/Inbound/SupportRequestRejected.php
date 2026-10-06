<?php

namespace App\Services\Support\Inbound;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

/**
 * An inbound call from the support assistant was refused. The reason is for our log; the caller only ever gets the status and a fixed
 * word for it, so it cannot tell a wrong signature from a replayed one or an unknown member from a suspended one.
 */
final class SupportRequestRejected extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $status = 401, ?Throwable $previous = null)
    {
        parent::__construct($reason, $status, $previous);
    }

    /** Thrown from a controller (a limit reached while reading) it is answered the same way as from the middleware, and is not an error to report. */
    public function render(): JsonResponse
    {
        return $this->toResponse();
    }

    public function report(): bool
    {
        return false;
    }

    public function toResponse(): JsonResponse
    {
        $word = match ($this->status) {
            403 => 'forbidden',
            404 => 'not_found',
            429 => 'rate_limited',
            503 => 'unavailable',
            default => 'unauthorized',
        };

        return response()->json(['error' => $word], $this->status, ['Cache-Control' => 'no-store']);
    }
}
