<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessageRequest;
use App\Http\Requests\UpdateContactMessageStatusRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ContactMessageController extends Controller
{
    /** GET /api/admin/contact-messages?search=&status=&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $query = ContactMessage::query();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(fn($q) => $q
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('subject', 'like', $like));
        }

        $status = $request->query('status');
        if ($status && in_array($status, ContactMessage::STATUSES, true)) {
            $query->where('status', $status);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);
        $messages = $query->latest()->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => ContactMessageResource::collection($messages->items()),
            'meta'    => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
                'unread_count' => ContactMessage::where('status', 'unread')->count(),
            ],
        ]);
    }

    /** GET /api/admin/contact-messages/{id} */
    public function show(int $id): JsonResponse
    {
        $message = ContactMessage::find($id);

        if (! $message) {
            return $this->notFound();
        }

        return response()->json(['success' => true, 'data' => new ContactMessageResource($message)]);
    }

    /** PATCH /api/admin/contact-messages/{id}/status */
    public function updateStatus(UpdateContactMessageStatusRequest $request, int $id): JsonResponse
    {
        $message = ContactMessage::find($id);

        if (! $message) {
            return $this->notFound();
        }

        $message->update(['status' => $request->validated('status')]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated.',
            'data'    => new ContactMessageResource($message->fresh()),
        ]);
    }

    /** DELETE /api/admin/contact-messages/{id} */
    public function destroy(int $id): JsonResponse
    {
        $message = ContactMessage::find($id);

        if (! $message) {
            return $this->notFound();
        }

        $message->delete();

        return response()->json(['success' => true, 'message' => 'Message deleted.']);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Message not found.'], 404);
    }

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
