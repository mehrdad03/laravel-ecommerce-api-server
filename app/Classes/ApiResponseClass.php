<?php

namespace App\Classes;

class ApiResponseClass
{
    public static function apiResponse($status, $message, $data, $code)
    {
        return response()->json([
            'success' => $status,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function errorResponse($error, $message, $code)
    {
        return response()->json([
            'error' => $error,
            'message' => $message,
        ], $code);
    }
}
