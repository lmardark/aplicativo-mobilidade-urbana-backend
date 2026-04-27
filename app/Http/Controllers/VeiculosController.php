<?php

namespace App\Http\Controllers;

use App\Models\Veiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VeiculosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Veiculo::paginate();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request->input();
        $veiculo =  Veiculo::create($request->input());
        return response()->json([
            'success' => true,
            'message' => 'Registro realizado com sucesso',
            'data' => $veiculo
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($veiculoId)
    {
        return Veiculo::findOrFail($veiculoId);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $veiculoId)
    {
        $veiculo = Veiculo::findOrFail($veiculoId)->update($request->input());
        return response()->json([
            'success' => true,
            'message' => 'Registro atualizado com sucesso',
            'data' => $veiculo
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Veiculo $veiculos)
    {
        //
    }

    public function veiculoPorPlaca($placa)
    {

        return Veiculo::where('placa', 'ilike', "%{$placa}%")->get();
        // $dados = DB::table('vaeiculos')
        //     ->select('vaeiculos.id as veiculo_id', 'motoristas.nome', 'vaeiculos.data_midia', 'vaeiculos.titulo', 'vaeiculos.cliente_id', 'motoristas.cnpj', 'vaeiculos.status_id', 'vaeiculos.empresa_contrato_id', 'empresa_contratos.numero_contrato', 'status.nome as status', 'vaeiculos.valor')
        //     ->leftJoin('motoristas', 'vaeiculos.cliente_id', '=', 'motoristas.id')
        //     ->leftJoin('status', 'vaeiculos.status_id', '=', 'status.id')
        //     ->where('vaeiculos.deleted_at', '=', null)
        //     ->orderBy('vaeiculos.id', 'desc')
        //     ->get();

        //     $this->filtrarPorPlaca($dados, $placa) : $dados;
        // return $data;
    }

    public function filtrarPorPlaca($dados, $palavraChave)
    {
        $objetos = json_decode($dados);

        $objetosFiltrados = array_filter($objetos, function ($objeto) use ($palavraChave) {
            return preg_match("/$palavraChave/i", $objeto->nome_fantasia);
        });

        return array_values($objetosFiltrados);
    }
}
