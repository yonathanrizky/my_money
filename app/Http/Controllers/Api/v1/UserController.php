<?php

namespace App\Http\Controllers\API\v1;

use App\Balance;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::customClaims(['email' => $request->email])->attempt($credentials)) {
                return ResponseFormatter::error([
                    'error' => 'email not found'
                ], 'Login fails', 400);
            }
        } catch (JWTException $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'Login fails', 400);
        }

        return ResponseFormatter::success([
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 'Login Success');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([
                'error' => $validator->errors()
            ], 'Register fails', 400);
        }

        $data = [
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'confirmation_code' => md5(mt_rand())
        ];

        $user = User::create($data);
        unset($data['password']);

        $balance = Balance::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        Mail::send('mail.verify', $data, function ($message) use ($request) {
            $message->to($request->email, $request->name)
                ->subject('Verify Your Email Address');
        });

        $token = JWTAuth::fromUser($user);
        return ResponseFormatter::success([
            'user' => $user,
            'balance' => $balance,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 'Register Success');
    }

    public function getAuthenticatedUser()
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }
        return response()->json(compact('user'));
    }
}
