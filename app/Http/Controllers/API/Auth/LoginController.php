<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard */
        $guard = auth('jwt');

        if (! ($user = User::where('email', $email)->first())) {
            return response()->json([
                'message' => 'Email ou senha inválidos',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$token = $guard->attempt(['email' => $email, 'password' => $password])) {
            return response()->json([
                'message' => 'Email ou senha inválidos',
            ], Response::HTTP_UNAUTHORIZED);
        };

        $ttl = $guard->getTTL();

        $cookie = cookie(
            name: 'token',
            value: $token,
            minutes: $ttl,
            path: '/',
            domain: null,
            secure: env('APP_ENV') === 'production',
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        );

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                "telefone" => $user->telefone,
                "cpf" => $user->cpf,
                "data_nascimento" => $user->data_nascimento,
                "foto" => $user->foto,
            ],
            'message' => 'Login realizado com sucesso.',
            'token' => $token
        ])->withCookie($cookie);
    }
}
