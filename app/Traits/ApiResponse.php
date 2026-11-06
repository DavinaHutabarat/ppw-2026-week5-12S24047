<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponse
{
    /**
     * Respon sukses standar (200 OK atau kode custom 2xx).
     */
    public function successResponse(mixed $data = null, ?string $message = null, int $code = 200, array $headers = []): JsonResponse
    {
        $payload = [];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            if (is_array($data) && array_key_exists('data', $data)) {
                $payload = array_merge($payload, $data);
            } else {
                $payload['data'] = $data;
            }
        }

        return response()->json($payload, $code, $headers);
    }

    /**
     * Respon pembuatan resource (201 Created) disertai header Location.
     */
    public function createdResponse(mixed $data, string $locationUrl, ?string $message = null): JsonResponse
    {
        $payload = [];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if (is_array($data) && array_key_exists('data', $data)) {
            $payload = array_merge($payload, $data);
        } else {
            $payload['data'] = $data;
        }

        return response()->json($payload, 201, [
            'Location' => $locationUrl,
        ]);
    }

    /**
     * Respon tanpa konten (204 No Content).
     */
    public function noContentResponse(): Response
    {
        return response()->noContent();
    }

    /**
     * Respon rincian masalah RFC 7807 (application/problem+json).
     */
    public function problemResponse(
        string $type,
        string $title,
        int $status,
        string $detail,
        ?string $instance = null,
        array $errors = []
    ): JsonResponse {
        $problem = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $instance ?? request()->getRequestUri(),
        ];

        if (!empty($errors)) {
            $problem['errors'] = $errors;
        }

        return response()->json($problem, $status, [
            'Content-Type' => 'application/problem+json',
        ]);
    }
}
