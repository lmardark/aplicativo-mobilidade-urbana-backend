<?php

namespace App\Http\Controllers;

use App\Models\Motorista;
use App\Models\MotoristaVeiculo;
use Illuminate\Http\Request;

class MotoristaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Motorista::with('user')->orderBy('id', 'DESC')->paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $veiculo =  Motorista::create($request->input());
        return response()->json([
            'success' => true,
            'message' => 'Registro realizado com sucesso',
            'data' => $veiculo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $motoristaid)
    {
        return Motorista::with('user')->findOrFail($motoristaid);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $motoristaid)
    {
        $veiculo = Motorista::findOrFail($motoristaid)->update($request->input());
        return response()->json([
            'success' => true,
            'message' => 'Registro atualizado com sucesso',
            'data' => $veiculo
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Motorista $motorista)
    {
        //
    }

    public function adicionarVeiculoAoMotorista(Request $request)
    {
        $motoristaVeiculo = MotoristaVeiculo::create($request->input());
        return response()->json([
            'success' => true,
            'message' => 'Registro realizado com sucesso',
            'data' => $motoristaVeiculo
        ], 201);
    }

    public function motoristaVeiculos(int $motoristaid)
    {
        return MotoristaVeiculo::with(['motorista', 'veiculo'])
            ->where('motorista_id', $motoristaid)->paginate();
    }
}
