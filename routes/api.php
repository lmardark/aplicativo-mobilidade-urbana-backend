<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\LogoutController;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\CorridaController;
use App\Http\Controllers\EstimativasController;
use App\Http\Controllers\MotoristaController;
use App\Http\Controllers\MotoristaDocumentoController;
use App\Http\Controllers\PassageiroController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\VeiculosController;
use App\Models\Passageiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', LoginController::class)->name('auth.login');

Route::middleware('auth:jwt')->group(function () {
    Route::post('auth/logout', LogoutController::class)->name('auth.logout');

    Route::apiResource('users', UsuarioController::class);
    Route::get('usuario-logado', [UsuarioController::class, 'usuarioLogado']);
    Route::delete('usuario-remover-foto-perfil/{id}', [UsuarioController::class, 'removerFotoPerfil']);
    Route::post('usuario-arquivar', [UsuarioController::class, 'usuarioArquivar']);
    Route::post('usuario-deletar', [UsuarioController::class, 'usuarioDeletar']);
    Route::post('usuario-restaurar', [UsuarioController::class, 'usuarioRestaurar']);
    Route::get('usuarios-arquivados', [UsuarioController::class, 'usuariosArquivados']);
    Route::put('usuario-alterar-foto-perfil/{id}', [UsuarioController::class, 'alterarFotoPerfil']);

    Route::apiResource('veiculos', VeiculosController::class);
    // Route::get('/veicu-por-placa', VeiculosController::class);
    Route::get('veiculo-por-placa/{placa}', [VeiculosController::class, 'veiculoPorPlaca']);

    Route::get('motorista-veiculos/{motoristaId}', [MotoristaController::class, 'motoristaVeiculos']);

    Route::apiResource('motoristas', MotoristaController::class);
    Route::post('adicionar-veiculo-ao-motorista', [MotoristaController::class, 'adicionarVeiculoAoMotorista']);

    Route::apiResource('motorista-documentos', MotoristaDocumentoController::class);
    Route::put('mudar-status-documento/{MotoristaDocumentoId}', [MotoristaDocumentoController::class, 'mudarStatusDocumento']);

    Route::apiResource('passageiros', PassageiroController::class);
    Route::get('passageiros-arquivados', [PassageiroController::class, 'PassageirosArquivados']);

    Route::apiResource('corridas', CorridaController::class);
    Route::get('corridas-negociada', [CorridaController::class, 'simularCorridaNegociada']);
    Route::apiResource('corridas-negociacoes', CorridaController::class);
    Route::apiResource('tarifas', TarifaController::class);
    Route::get('corridas-buscar', [CorridaController::class, 'buscarEndereco']);
    Route::get('calculos-entre-endereco', [CorridaController::class, 'calculoEntreEnderecos']);
    // Route::get('corridas-motorista-para-origem', [CorridaController::class, 'motoristaParaOrigem']);





    // /calculo
    Route::prefix('estimativa')->group(function () {
        // /calculo/rota/estimada
        Route::get('rota/distancia', [EstimativasController::class, 'estimarRota']);
        /**
         * /users GET index
         * /users/{id} GET show
         * /users POST create
         * /users/{id} PUT|PATCH update
         * /users/{id} DELETE destroy
         * /users/{id}/archive DELETE delete()
         * /users/{id}/restore PATCH restore()
         * /users/{id}/ride GET RideController::index()
         * /users/{id}/ride/{id} GET RideController::show()
         * /usuario/{id}/corrida/{id} GET RideController::show()
         * 
         * /calculo/
         */
    });

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
