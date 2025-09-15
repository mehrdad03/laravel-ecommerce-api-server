<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v= Validator::make($request->all(),[
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($v->fails()){
            return ApiResponseClass::errorResponse(
                'Validation Error',
                $v->errors(),
                422
            );
        }

        $user=User::query()->create([
            'name'=>$request->string('name'),
            'email'=>$request->string('email'),
            'password'=>Hash::make($request->string('password')),
        ]);
        $token=$user->createToken('mobile')->plainTextToken;

        return ApiResponseClass::apiResponse('true', 'Registered successful', [
            'user' => $user,
            'token' => $token,
        ], 200);
    }
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

        $token = $user->createToken('mobile')->plainTextToken;

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
        return ApiResponseClass::apiResponse('true', 'Authenticated user', $request->user(), 200);

    }

}
