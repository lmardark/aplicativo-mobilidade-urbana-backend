<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
use App\Models\ProdutoCorrida;
use App\Services\SimularCorridaNegociadaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        // passageiro escolhe o produto

        // calculo passa a passo para gerar o valor da negociacao inicial que aparece na tela do usuário
        // ...

        $prudutoEscolhido = 'negocia';
        // $valorInicialQueApareceNatelaDousuario = ?
        $produto = ProdutoCorrida::where('codigo', $prudutoEscolhido)->first();
    }

    // public function simularCorridaNegociada(Corrida $corrida)
    // {
    //     $service = new SimularCorridaNegociadaService();

    //     $resultado = $service->executar([
    //         'distancia_km' => 7.8,
    //         'tempo_min' => 16,
    //         'diferenca_negociada' => 2.68,
    //     ]);
    //     return response()->json($resultado);
    // }

    public function buscarEndereco()
    {
        $endereco = 'AVENIDA NAÇÕES UNIDAS 555 PORTO VELHO';

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

    public function calculoEntreEnderecos()
    {
        $EnderecoMotorista = [
            "endereco_formatado" => "R. Coimbra, 4994 - Flodoaldo Pontes Pinto, Porto Velho - RO, 76820-556, Brazil",
            "latitude" => -8.7491451,
            "longitude" => -63.8662573
        ];

        $EnderecoOrigemCorrida = [
            "endereco_formatado" => "R. Coimbra, 5205 - Conj. 4 de Janeiro, Porto Velho - RO, 76820-556, Brazil",
            "latitude" => -8.7478987,
            "longitude" => -63.864684
        ];

        $EnderecoDestinoFinal = [
            "endereco_formatado" => "Av. Nações Unidas, 555 - Km 1, Porto Velho - RO, 76804-175, Brazil",
            "latitude" => -8.765801,
            "longitude" => -63.8926692
        ];

        /*
    |--------------------------------------------------------------------------
    | MOTORISTA -> ORIGEM
    |--------------------------------------------------------------------------
    */

        $motoristaParaOrigem = $this->calcularRota(
            $EnderecoMotorista['latitude'],
            $EnderecoMotorista['longitude'],
            $EnderecoOrigemCorrida['latitude'],
            $EnderecoOrigemCorrida['longitude']
        );

        /*
    |--------------------------------------------------------------------------
    | ORIGEM -> DESTINO
    |--------------------------------------------------------------------------
    */

        $origemParaDestino = $this->calcularRota(
            $EnderecoOrigemCorrida['latitude'],
            $EnderecoOrigemCorrida['longitude'],
            $EnderecoDestinoFinal['latitude'],
            $EnderecoDestinoFinal['longitude']
        );

        /*
    |--------------------------------------------------------------------------
    | TOTAL
    |--------------------------------------------------------------------------
    */

        $distanciaTotalKm =
            $motoristaParaOrigem['distancia_km'] +
            $origemParaDestino['distancia_km'];

        $tempoTotalMinutos =
            $motoristaParaOrigem['tempo_minutos'] +
            $origemParaDestino['tempo_minutos'];

        return response()->json([

            /*
        |--------------------------------------------------------------------------
        | MOTORISTA -> ORIGEM
        |--------------------------------------------------------------------------
        */

            'motorista_para_origem' => [
                'origem' => $EnderecoMotorista['endereco_formatado'],
                'destino' => $EnderecoOrigemCorrida['endereco_formatado'],
                'distancia_km' => $motoristaParaOrigem['distancia_km'],
                'tempo_minutos' => $motoristaParaOrigem['tempo_minutos'],
            ],

            /*
        |--------------------------------------------------------------------------
        | ORIGEM -> DESTINO
        |--------------------------------------------------------------------------
        */

            'origem_para_destino' => [
                'origem' => $EnderecoOrigemCorrida['endereco_formatado'],
                'destino' => $EnderecoDestinoFinal['endereco_formatado'],
                'distancia_km' => $origemParaDestino['distancia_km'],
                'tempo_minutos' => $origemParaDestino['tempo_minutos'],
            ],

            /*
        |--------------------------------------------------------------------------
        | TOTAL GERAL
        |--------------------------------------------------------------------------
        */

            'total_geral' => [
                'distancia_total_km' => round($distanciaTotalKm, 2),
                'tempo_total_minutos' => round($tempoTotalMinutos),
            ]
        ]);
    }

    /*
|--------------------------------------------------------------------------
| CALCULAR ROTA
|--------------------------------------------------------------------------
*/

    private function calcularRota(
        float $origemLat,
        float $origemLng,
        float $destinoLat,
        float $destinoLng
    ) {

        $url = "https://router.project-osrm.org/route/v1/driving/" .
            "{$origemLng},{$origemLat};{$destinoLng},{$destinoLat}" .
            "?overview=false";

        $response = Http::get($url);

        $data = $response->json();

        if (
            empty($data['routes']) ||
            !isset($data['routes'][0])
        ) {
            return [
                'distancia_km' => 0,
                'tempo_minutos' => 0,
            ];
        }

        $rota = $data['routes'][0];

        $distanciaKm = $rota['distance'] / 1000;

        $tempoMinutos = $rota['duration'] / 60;

        return [
            'distancia_km' => round($distanciaKm, 2),
            'tempo_minutos' => round($tempoMinutos),
        ];
    }
}
