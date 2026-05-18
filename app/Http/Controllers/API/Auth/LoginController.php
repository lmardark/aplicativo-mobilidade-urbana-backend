<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\CodigoVerificacao;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use phpDocumentor\Reflection\Types\Boolean;
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


    public function enviarCodigo(Request $request)
    {
        $request->validate([
            'telefone' => 'required'
        ]);
        $telefone = preg_replace('/\D/', '', $request->telefone);

        // código fake
        $codigo = rand(1000, 9999);

        Cache::put("codigo_verificacao:$telefone", $codigo, 60);

        return response()->json([
            'message' => 'Código enviado',
            'codigo' => $codigo // TEMPORÁRIO
        ]);
    }

    public function verificarCodigo(Request $request)
    {
        $request->validate([
            'telefone' => 'required',
            'codigo' => 'required'
        ]);

        $telefone = preg_replace('/\D/', '', $request->telefone);

        $registro = Cache::get("codigo_verificacao:$telefone");

        if (!$registro) {
            return response()->json([
                'message' => 'Código inválido'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // procura usuário
        $user = User::where('telefone', $telefone)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Usuário não encontrado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard */
        $guard = auth('jwt');

        // gera token JWT
        $token = $guard->login($user);

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

        // remove código após uso
        Cache::forget("codigo_verificacao:$telefone");

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telefone' => $user->telefone,
                'cpf' => $user->cpf,
                'data_nascimento' => $user->data_nascimento,
                'foto' => $user->foto,
            ],
            'message' => 'Login realizado com sucesso.',
            'token' => $token
        ])->withCookie($cookie);
    }

    public function verificaSeContaExiste(Request $request)
    {
        $request->validate([
            'telefone' => 'required'
        ]);
        $telefone = preg_replace('/\D/', '', $request->telefone);
        $user = User::where('telefone', $telefone)->first();
        $contaExiste = $user ? true : false;
        return response()->json([
            'contaExiste' => $contaExiste
        ]);
    }
}
