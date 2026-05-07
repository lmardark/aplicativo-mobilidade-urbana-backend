<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
use App\Models\ProdutoCorrida;
use App\Services\SimularCorridaNegociadaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EstimativasController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function estimativaRotaMotoristaParaOrigem()
    {

        $EnderecoMotorista = [
            "endereco_formatado" => "Rua brasilia 2930 porto velho",
            "latitude" => -8.7553147,
            "longitude" => -63.8973759,
            "endereco" => 'motorista',
        ];

        $EnderecoOrigemCorrida = [
            "endereco_formatado" => "R. Coimbra, 5205 - Conj. 4 de Janeiro, Porto Velho - RO, 76820-556, Brazil",
            "latitude" => -8.7478987,
            "longitude" => -63.864684,
            "endereco" => 'origem',

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

        return response()->json([
            'motorista_para_origem' => [
                'origem' => $EnderecoMotorista['endereco_formatado'],
                'destino' => $EnderecoOrigemCorrida['endereco_formatado'],
                'distancia_km' => $motoristaParaOrigem['distancia_km'],
                'tempo_minutos' => $motoristaParaOrigem['tempo_minutos'],
            ],
        ]);
    }

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




    public function estimativaRotaOrigemDestino()
    {
        $enderecos = [
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
                "ordem" => 1, // parada
            ],
            [
                "endereco_formatado" => "Av. Nações Unidas, 555 - Km 1, Porto Velho - RO, 76804-175, Brazil",
                "latitude" => -8.765801,
                "longitude" => -63.8926692,
                "ordem" => 2, // destino final
            ]
        ];

        /*
    |--------------------------------------------------------------------------
    | ORDENA ENDEREÇOS
    |--------------------------------------------------------------------------
    */

        usort($enderecos, function ($a, $b) {
            return $a['ordem'] <=> $b['ordem'];
        });

        /*
    |--------------------------------------------------------------------------
    | ORIGEM
    |--------------------------------------------------------------------------
    */

        $origem = $enderecos[0];

        /*
    |--------------------------------------------------------------------------
    | DESTINO FINAL
    |--------------------------------------------------------------------------
    */

        $destinoFinal = end($enderecos);

        /*
    |--------------------------------------------------------------------------
    | PARADAS
    |--------------------------------------------------------------------------
    */

        $paradas = [];

        if (count($enderecos) > 2) {

            $paradas = array_slice(
                $enderecos,
                1,
                count($enderecos) - 2
            );
        }

        /*
    |--------------------------------------------------------------------------
    | CALCULAR ROTA
    |--------------------------------------------------------------------------
    */

        $rota = $this->calcularRotaGoogle(
            $origem,
            $destinoFinal,
            $paradas
        );

        /*
    |--------------------------------------------------------------------------
    | RETORNO
    |--------------------------------------------------------------------------
    */

        return response()->json([

            'origem_para_destino' => [

                'origem' => [
                    'endereco' => $origem['endereco_formatado'],
                    'latitude' => $origem['latitude'],
                    'longitude' => $origem['longitude'],
                ],

                'paradas' => collect($paradas)->map(function ($parada) {

                    return [
                        'endereco' => $parada['endereco_formatado'],
                        'latitude' => $parada['latitude'],
                        'longitude' => $parada['longitude'],
                    ];
                })->values(),

                'destino' => [
                    'endereco' => $destinoFinal['endereco_formatado'],
                    'latitude' => $destinoFinal['latitude'],
                    'longitude' => $destinoFinal['longitude'],
                ],

                'distancia_km' => $rota['distancia_km'],

                'tempo_minutos' => $rota['tempo_minutos'],
            ]
        ]);
    }

    /*
|--------------------------------------------------------------------------
| CALCULAR ROTA GOOGLE
|--------------------------------------------------------------------------
*/

    private function calcularRotaGoogle(
        array $origem,
        array $destino,
        array $paradas = []
    ) {

        /*
    |--------------------------------------------------------------------------
    | WAYPOINTS (PARADAS)
    |--------------------------------------------------------------------------
    */

        $waypoints = null;

        if (!empty($paradas)) {

            $waypoints = collect($paradas)
                ->map(function ($parada) {

                    return $parada['latitude'] .
                        ',' .
                        $parada['longitude'];
                })
                ->implode('|');
        }

        /*
    |--------------------------------------------------------------------------
    | REQUEST GOOGLE DIRECTIONS API
    |--------------------------------------------------------------------------
    */

        $response = Http::get(
            'https://maps.googleapis.com/maps/api/directions/json',
            [
                'origin' =>
                $origem['latitude'] . ',' . $origem['longitude'],

                'destination' =>
                $destino['latitude'] . ',' . $destino['longitude'],

                'waypoints' => $waypoints,

                'mode' => 'driving',

                'language' => 'pt-BR',

                'key' => env('GOOGLE_MAPS_API_KEY'),
            ]
        );

        $data = $response->json();

        /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO
    |--------------------------------------------------------------------------
    */

        if (
            empty($data['routes']) ||
            !isset($data['routes'][0]['legs'])
        ) {
            return [
                'distancia_km' => 0,
                'tempo_minutos' => 0,
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | LEGS
    |--------------------------------------------------------------------------
    |
    | Cada trecho:
    | origem -> parada
    | parada -> parada
    | parada -> destino
    |
    */

        $legs = $data['routes'][0]['legs'];

        $distanciaMetros = 0;

        $tempoSegundos = 0;

        foreach ($legs as $leg) {

            $distanciaMetros += $leg['distance']['value'];

            $tempoSegundos += $leg['duration']['value'];
        }

        /*
    |--------------------------------------------------------------------------
    | CONVERSÕES
    |--------------------------------------------------------------------------
    */

        $distanciaKm = $distanciaMetros / 1000;

        $tempoMinutos = $tempoSegundos / 60;

        return [
            'distancia_km' => round($distanciaKm, 2),
            'tempo_minutos' => round($tempoMinutos),
        ];
    }

    private function calcularRotas(
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
    private function calcularRota(
        float $origemLat,
        float $origemLng,
        float $destinoLat,
        float $destinoLng
    ) {

        $response = Http::get(
            'https://maps.googleapis.com/maps/api/directions/json',
            [
                'origin' => "{$origemLat},{$origemLng}",
                'destination' => "{$destinoLat},{$destinoLng}",
                'mode' => 'driving',
                'language' => 'pt-BR',
                'key' => env('GOOGLE_MAPS_API_KEY'),
            ]
        );

        $data = $response->json();

        /*
    |--------------------------------------------------------------------------
    | VALIDAÇÃO
    |--------------------------------------------------------------------------
    */

        if (
            empty($data['routes']) ||
            !isset($data['routes'][0]['legs'][0])
        ) {
            return [
                'distancia_km' => 0,
                'tempo_minutos' => 0,
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | LEG
    |--------------------------------------------------------------------------
    */

        $leg = $data['routes'][0]['legs'][0];

        /*
    |--------------------------------------------------------------------------
    | DISTÂNCIA
    |--------------------------------------------------------------------------
    */

        $distanciaMetros = $leg['distance']['value'];

        $distanciaKm = $distanciaMetros / 1000;

        /*
    |--------------------------------------------------------------------------
    | TEMPO
    |--------------------------------------------------------------------------
    */

        $tempoSegundos = $leg['duration']['value'];

        $tempoMinutos = $tempoSegundos / 60;

        return [
            'distancia_km' => round($distanciaKm, 2),
            'tempo_minutos' => round($tempoMinutos),
        ];
    }
}
