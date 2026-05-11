<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
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
        // passageiro escolhe o produto

        // calculo passa a passo para gerar o valor da negociacao inicial que aparece na tela do usuário
        // ...

        $prudutoEscolhido = 'negocia';
        $produto = ProdutosCorrida::where('codigo', $prudutoEscolhido)->first();

        $intinerarioPassageiro = [
            [
                "endereco_formatado" => "R. Coimbra, 5205 - Conj. 4 de Janeiro, Porto Velho - RO, 76820-556, Brazil",
                "latitude" => -8.7478987,
                "longitude" => -63.864684,
                "ordem" => 0, // parada
            ],
            [
                "endereco_formatado" => "Av. Nações Unidas, 555 - Km 1, Porto Velho - RO, 76804-175, Brazil",
                "latitude" => -8.765801,
                "longitude" => -63.8926692,
                "ordem" => 1, // destino final
            ]
        ];

        $intinerarioMotoristaAteOrigem = [
            [
                "endereco_formatado" => "R. Coimbra, 4994 - Flodoaldo Pontes Pinto, Porto Velho - RO, 76820-556, Brazil",
                "latitude" => -8.7491451,
                "longitude" => -63.8662573,
                "ordem" => 0, // origem
            ],
            [
                "endereco_formatado" => "R. Coimbra, 5205 - Conj. 4 de Janeiro, Porto Velho - RO, 76820-556, Brazil",
                "latitude" => -8.7478987,
                "longitude" => -63.864684,
                "ordem" => 1,
            ],
        ];

        $estimativaIntinerarioPassageiro = $this->estimarRotaService->executar(enderecos: $intinerarioPassageiro);
        $estimativaIntinerarioMotoristaAteOrigem = $this->estimarRotaService->executar(enderecos: $intinerarioMotoristaAteOrigem);

        $tempoIntinerarioMotoristaAteOrigem = $estimativaIntinerarioMotoristaAteOrigem['tempo_minutos'];
        $distanciaIntinerarioMotoristaAteOrigem = $estimativaIntinerarioMotoristaAteOrigem['tempodistancia_kmminutos'];

        $tempoIntinerarioPassageiro = $estimativaIntinerarioPassageiro['tempo_minutos'];
        $distanciaIntinerarioPassageiro = $estimativaIntinerarioPassageiro['distancia_km'];

        $tempoTotalIntinarantes = $tempoIntinerarioMotoristaAteOrigem + $tempoIntinerarioPassageiro;
        $distanciaTotalIntinerarios = $distanciaIntinerarioMotoristaAteOrigem + $distanciaIntinerarioPassageiro;
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
    {;
        $endereco = $request->endereco ?? '';
        $response = Http::get(
            'https://maps.googleapis.com/maps/api/geocode/json',
            [
                'address' => $endereco,
                'key' => env('GOOGLE_MAPS_API_KEY'),
            ]
        );

        $data = $response->json();

        if (empty($data['results'])) {
            return null;
        }

        $resultado = $data['results'][0];

        return [
            'endereco_formatado' => $resultado['formatted_address'],
            'latitude' => $resultado['geometry']['location']['lat'],
            'longitude' => $resultado['geometry']['location']['lng'],
        ];
    }
}
