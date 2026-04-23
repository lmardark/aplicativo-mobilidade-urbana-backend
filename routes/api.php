<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\UsuarioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', LoginController::class)->name('auth.login');

Route::middleware('auth:jwt')->group(function () {
    Route::post('auth/logout', LogoutController::class)->name('auth.logout');
    Route::apiResource('/users', UsuarioController::class);
    Route::get('usuario-logado', [UsuarioController::class, 'usuarioLogado']);
    Route::delete('usuario-remover-foto-perfil/{id}', [UsuarioController::class, 'removerFotoPerfil']);
    Route::get('/user', function (Request $request) {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard */
        $guard = auth('jwt');
        $uid = $guard->payload()->get('uid');

        return [
            'user' => $request->user(),
            'uid' => $uid,
        ];
    });
});
