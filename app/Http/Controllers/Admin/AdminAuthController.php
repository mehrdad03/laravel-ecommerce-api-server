<?php

namespace App\Http\Controllers\Admin;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return ApiResponseClass::errorResponse(
                'Validation Error',
                $validator->errors(),
                422
            );
        }


        $credentials = $validator->validated();
        if (!Auth::attempt($credentials)) {

            return ApiResponseClass::errorResponse(
                'Unauthorized',
                'Invalid credentials',
                422
            );
        }

        $user = Auth::user();
        if (!$user->is_admin) {
            return ApiResponseClass::errorResponse('Forbidden', 'Access denied', 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return ApiResponseClass::apiResponse('true', 'Login Successful', [
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return ApiResponseClass::apiResponse('true', 'Logout Successful', null, 200);
    }

    public function me(Request $request)
    {
        return ApiResponseClass::apiResponse('true', 'Authenticated admin', $request->user(), 200);

    }
}
