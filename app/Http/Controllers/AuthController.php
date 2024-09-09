<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator; // Importar Validator facade

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh', 'validateToken']]);
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

    public function register(Request $request)
    {
        // Validación manual usando Validator facade en Lumen
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            // Si la validación falla, devolver los errores
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Crear el usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), // Usar Hash::make para encriptar la contraseña
            ]);

            // Autenticar al usuario y generar el token JWT
            $credentials = $request->only('email', 'password');
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $refreshToken = $this->createRefreshToken($user->id);
            // Devolver el token y la información del usuario
            return $this->respondWithToken($token, $refreshToken);
        } catch (\Exception $e) {
            // Si ocurre algún error, devolver una respuesta de error
            return response()->json(['error' => 'Failed to register user, please try again'], 500);
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
            // Recuperar el refresh token de la solicitud
            $refreshToken = $request->input('refresh_token');

            // Verificar si el refresh token está presente en la base de datos
            $storedToken = DB::table('refresh_tokens')->where('refresh_token', $refreshToken)->first();

            // Si el token no existe o ha expirado, devolver error
            if (!$storedToken || Carbon::parse($storedToken->expires_at)->isPast()) {
                return response()->json(['error' => 'Invalid or expired refresh token'], 401);
            }

            // Obtener el usuario asociado al refresh token
            $user = User::find($storedToken->user_id);



            // Si el usuario no existe, devolver error
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }


            // Generar un nuevo access token (JWT)
            $newToken = JWTAuth::fromUser($user);


            // Generar un nuevo refresh token y reemplazar el anterior en la base de datos
            $newRefreshToken = $this->createRefreshToken($user->id);

            //dd($newToken, $newRefreshToken);

            // Responder con el nuevo token y el refresh token
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
            // Forzar la autenticación con el nuevo token para asegurarte de que el usuario esté disponible
            JWTAuth::setToken($token); // Establece el token generado para el contexto actual

            // Obtener el usuario autenticado
            $user = JWTAuth::authenticate();

            if (!$user) {
                // Si no hay usuario autenticado, devolver un error
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Cargar las relaciones si el usuario está autenticado
            $user->load('roles', 'roles.permissions');

            $expiresInMinutes = config('jwt.ttl'); // Tiempo en minutos desde la configuración
            $expiresInSeconds = $expiresInMinutes * 60; // Convertir minutos a segundos

            return response()->json([
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'user' => $user,
                'expires_in' => $expiresInSeconds,
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


    public function testTokenTTL()
    {
        $ttl = config('jwt.ttl');
        return response()->json(['ttl' => $ttl]);
    }

    public function tokenLife(Request $request)
    {
        try {
            // Obtener el token actual desde la solicitud
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }

            // Decodificar el token sin necesidad de verificar
            $payload = JWTAuth::setToken($token)->getPayload();

            // Obtener el tiempo de expiración (exp) del payload
            $exp = $payload->get('exp');

            // Obtener el tiempo actual en formato de timestamp (segundos desde 1970)
            $currentTime = Carbon::now()->timestamp;

            // Calcular cuánto tiempo queda hasta que expire el token
            $timeLeft = $exp - $currentTime;

            if ($timeLeft <= 0) {
                return response()->json(['message' => 'Token has already expired'], 401);
            }

            return response()->json([
                'message' => 'Token is still valid',
                'time_left_in_seconds' => $timeLeft,
                'expires_at' => Carbon::createFromTimestamp($exp)->toDateTimeString(),
                'server_time' => Carbon::now()->toDateTimeString() // Hora actual del servidor
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get token life', 'details' => $e->getMessage()], 500);
        }
    }
}
