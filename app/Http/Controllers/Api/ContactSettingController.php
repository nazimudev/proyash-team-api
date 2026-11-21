<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactSettingRequest;
use App\Models\ContactSetting;
use Illuminate\Http\JsonResponse;

class ContactSettingController extends Controller
{
    /** GET /api/contact-settings (public) and GET /api/admin/contact-settings (admin) */
    public function show(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->payload(ContactSetting::current())]);
    }

    /** PUT|PATCH /api/admin/contact-settings */
    public function update(UpdateContactSettingRequest $request): JsonResponse
    {
        $settings = ContactSetting::current();
        $settings->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Contact settings saved.',
            'data'    => $this->payload($settings->fresh()),
        ]);
    }

    // Only fields that are meant to be public are exposed.
    private function payload(ContactSetting $s): array
    {
        return [
            'phone'         => $s->phone,
            'email'         => $s->email,
            'working_hours' => $s->working_hours,
            'map_embed_url' => $s->map_embed_url,
        ];
    }
}
