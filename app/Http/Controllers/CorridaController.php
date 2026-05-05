<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
use App\Services\SimularCorridaNegociadaService;
use Illuminate\Http\Request;

class CorridaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Corrida::with(
            [
                'motorista.user',
                'passageiro.user',
                'veiculo',
                'corrida_destinos',
                'corrida_financeiro.corrida_desconto',
            ]
        )->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Corrida $corrida)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Corrida $corrida)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Corrida $corrida)
    {
        //
    }

    public function simularCorridaNegociada(Corrida $corrida)
    {
        $service = new SimularCorridaNegociadaService();

        $resultado = $service->executar([
            'distancia_km' => 7.8,
            'tempo_min' => 16,
            'diferenca_negociada' => 2.68,
        ]);
        return response()->json($resultado);
    }
}
