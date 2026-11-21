<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ApiResponses
{
    /** { success: true, message, data } */
    protected function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * List shape the frontend reads:
     * data: { data: [...], current_page, last_page, per_page, total }
     */
    protected function paginated(LengthAwarePaginator $paginator, string $resourceClass, Request $request, string $message = 'OK'): JsonResponse
    {
        return $this->success([
            'data' => $resourceClass::collection(collect($paginator->items()))->resolve($request),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ], $message);
    }
}
