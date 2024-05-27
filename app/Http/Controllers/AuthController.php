<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'validateToken']]);
    }

    public function login(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json(['message' => 'Usuario y/o contraseña incorrectos'], 400);
            }

            $credentials = $request->only('email', 'password');
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $refreshToken = $this->createRefreshToken($user->id);
            return $this->respondWithToken($token, $refreshToken);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to login, please try again'], 500);
        }
    }

    public function me()
    {
        try {
            $user = auth()->user()->load('roles', 'roles.permissions');
            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get user details'], 500);
        }
    }

    public function logout()
    {
        try {
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::invalidate($token);
            }
            auth()->logout();

            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $refreshToken = $request->input('refresh_token');
            $storedToken = DB::table('refresh_tokens')->where('refresh_token', $refreshToken)->first();

            if (!$storedToken || Carbon::parse($storedToken->expires_at)->isPast()) {
                return response()->json(['error' => 'Invalid or expired refresh token'], 401);
            }

            $user = User::find($storedToken->user_id);
            $newToken = JWTAuth::fromUser($user);

            $newRefreshToken = $this->createRefreshToken($user->id);
            return $this->respondWithToken($newToken, $newRefreshToken);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to refresh token, please try again'], 500);
        }
    }

    public function validateToken(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], 404);
            }
            return response()->json(['valid' => true, 'user' => $user]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent'], 401);
        }
    }

    protected function respondWithToken($token, $refreshToken)
    {
        try {
            $user = auth()->user()->load('roles', 'roles.permissions');
            $expiresIn = auth()->factory()->getTTL() * 60; // Tiempo en segundos

            return response()->json([
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'user' => $user,
                'expires_in' => $expiresIn,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to respond with token'], 500);
        }
    }

    protected function createRefreshToken($userId)
    {
        $refreshToken = Str::random(60);
        $expiresAt = Carbon::now()->addDays(7); // Validez de 7 días

        DB::table('refresh_tokens')->insert([
            'user_id' => $userId,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ]);

        return $refreshToken;
    }
}

