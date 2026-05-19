<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
use App\Models\Passageiro;
use App\Models\ProdutosCorrida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\EstimarRotaService;

class CorridaController extends Controller
{
    public function __construct(
        protected EstimarRotaService $estimarRotaService
    ) {}

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
        $passageiroId = 1;
        $motoristaId = 1;
        $veiculoId = 1;
        $cidadeId = 18;
        $intinerarioPassageiro = [
            [
                "endereco_formatado" => "R. Coimbra, 5205 - Conj. 4 de Janeiro, Porto Velho - RO, 76820-556, Brazil",
                "latitude" => -8.7478987,
                "longitude" => -63.864684,
                "ordem" => 0,
            ],
            [
                "endereco_formatado" => "Av. Nações Unidas, 555 - Km 1, Porto Velho - RO, 76804-175, Brazil",
                "latitude" => -8.765801,
                "longitude" => -63.8926692,
                "ordem" => 1,
            ]
        ];

        $estimativaIntinerarioPassageiro = $this->estimarRotaService->executar(enderecos: $intinerarioPassageiro);
        $tempoIntinerarioPassageiro = $estimativaIntinerarioPassageiro['tempo_minutos'];
        $distanciaIntinerarioPassageiro = $estimativaIntinerarioPassageiro['distancia_km'];

        $prudutoEscolhido = 'negocia';
        $produto = ProdutosCorrida::where('codigo', $prudutoEscolhido)->first();

        $formaPagamento = 'dinheiro';
        $intinerarioMotoristaAteOrigem = [
            [
                "endereco_formatado" => "R. Coimbra, 4994 - Flodoaldo Pontes Pinto, Porto Velho - RO, 76820-556, Brazil",
                "latitude" => -8.7491451,
                "longitude" => -63.8662573,
                "ordem" => 0,
            ],
            [
                "endereco_formatado" => "R. Coimbra, 5205 - Conj. 4 de Janeiro, Porto Velho - RO, 76820-556, Brazil",
                "latitude" => -8.7478987,
                "longitude" => -63.864684,
                "ordem" => 1,
            ],
        ];
        $estimativaIntinerarioMotoristaAteOrigem = $this->estimarRotaService->executar(enderecos: $intinerarioMotoristaAteOrigem);
        $tempoIntinerarioMotoristaAteOrigem = $estimativaIntinerarioMotoristaAteOrigem['tempo_minutos'];
        $distanciaIntinerarioMotoristaAteOrigem = $estimativaIntinerarioMotoristaAteOrigem['tempodistancia_kmminutos'];
        $tempoTotalIntinarantes = $tempoIntinerarioMotoristaAteOrigem + $tempoIntinerarioPassageiro;
        $distanciaTotalIntinerarios = $distanciaIntinerarioMotoristaAteOrigem + $distanciaIntinerarioPassageiro;
        $contraPropostaMotorista = 0;
        $diferencaNegociada = 0;
        // aqui manda o service  SimularCorridaNegociadaService e manda as informacoes de intinerarios (distancia e tempo).
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


    public function buscarEndereco(Request $request)
    {
        $endereco = $request->endereco ?? '';

        if (empty($endereco)) {
            return response()->json(null);
        }

        // 🔹 Alterado para o endpoint 'textsearch' da Places API
        $response = Http::get(
            'https://maps.googleapis.com/maps/api/place/textsearch/json',
            [
                'query' => $endereco, // Mudou de 'address' para 'query'
                'key' => env('GOOGLE_MAPS_API_KEY'),
                'language' => 'pt-BR' // Força a resposta vir em português estruturado
            ]
        );

        $data = $response->json();

        if (empty($data['results'])) {
            return response()->json(null);
        }

        // Pega o primeiro resultado da busca
        $resultado = $data['results'][0];

        /* A estrutura retornada pelo Places API agora possui:
      $resultado['name'] -> "Porto Velho Shopping" ou "Residencial Porto Madero V"
      $resultado['formatted_address'] -> "Avenida Rio Madeira, 3288 - Flodoaldo Pontes Pinto..."
    */
        return response()->json([
            'name' => $resultado['name'] ?? '',
            'formattedAddress' => $resultado['formatted_address'],
            'latitude' => $resultado['geometry']['location']['lat'],
            'longitude' => $resultado['geometry']['location']['lng'],
        ]);
    }
}
