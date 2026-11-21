<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class ContactMessageController extends Controller
{
    /** POST /api/contact (public, throttled) */
    public function store(ContactMessageRequest $request): JsonResponse
    {
        $success = [
            'success' => true,
            'message' => 'Your message has been sent successfully.',
        ];

        // Honeypot: bots fill the hidden field. Pretend success, store nothing.
        if (filled($request->input('website'))) {
            return response()->json($success + ['data' => ['id' => null]], 201);
        }

        try {
            $contact = ContactMessage::create([
                ...$request->safe()->except('website'),
                'status'     => 'unread',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            ]);
        } catch (Throwable $e) {
            Log::error('Contact message store failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'We could not send your message right now. Please try again later.',
            ], 500);
        }

        // Only the id is returned; no private data leaves the server.
        return response()->json($success + ['data' => ['id' => $contact->id]], 201);
    }
}
