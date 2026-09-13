<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Standard success response.
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
            'code'    => $code,
        ], $code);
    }

    /**
     * Standard error response.
     */
    protected function error(string $message = 'Something went wrong', int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => $data,
            'code'    => $code,
        ], $code);
    }

    /**
     * Validation failure (422), pass $validator->errors() as $errors.
     */
    protected function validationError($errors, string $message = 'Validation failed', int $code = 422): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => $errors,
            'code'    => $code,
        ], $code);
    }

    protected function notFound(string $message = 'Resource not found', int $code = 404): JsonResponse
    {
        return $this->error($message, $code);
    }

    protected function unauthorized(string $message = 'Unauthorized', int $code = 401): JsonResponse
    {
        return $this->error($message, $code);
    }

    protected function forbidden(string $message = 'Forbidden', int $code = 403): JsonResponse
    {
        return $this->error($message, $code);
    }
}