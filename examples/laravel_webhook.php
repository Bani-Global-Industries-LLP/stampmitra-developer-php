<?php

declare(strict_types=1);

// Laravel controller sketch (not runnable on its own).
// routes/api.php:  Route::post('/webhooks/stampmitra', StampMitraWebhookController::class);
// Exclude the route from CSRF protection (it is an API route, so it already is).
// config/services.php: 'stampmitra' => ['webhook_secret' => env('STAMPMITRA_WEBHOOK_SECRET')]

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StampMitra\Exception\SignatureVerificationError;
use StampMitra\Webhook;

final class StampMitraWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // $request->getContent() is the raw body, exactly as sent. Do not use $request->all().
            $event = Webhook::constructEventFromHeaders(
                $request->getContent(),
                $request->headers->all(),
                (string) config('services.stampmitra.webhook_secret'),
            );
        } catch (SignatureVerificationError $e) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        // dispatch(new HandleStampMitraEvent($event->raw())); // queue the work, answer fast
        return response()->json(['received' => true]);
    }
}
