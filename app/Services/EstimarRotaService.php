<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EstimarRotaService
{

    /*
    |--------------------------------------------------------------------------
    | ORIGEM -> PARADAS -> DESTINO
    |--------------------------------------------------------------------------
    */

    public function executar(
        array $enderecos
    ): array {

        /*
        |--------------------------------------------------------------------------
        | ORDENA
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

        $origem = array_first($enderecos);

        /*
        |--------------------------------------------------------------------------
        | DESTINO
        |--------------------------------------------------------------------------
        */

        $destino = array_last($enderecos);

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

        $rota = $this->calcularRota(
            origem: $origem,
            destino: $destino,
            paradas: $paradas
        );


        $retorno = [

            'origem' => [
                'endereco' => $origem['endereco_formatado'],
                'latitude' => $origem['latitude'],
                'longitude' => $origem['longitude'],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | PARADAS
        |--------------------------------------------------------------------------
        |
        | Só adiciona no retorno se existir parada
        |
        */

        if (!empty($paradas)) {

            $retorno['paradas'] = collect($paradas)->map(function ($parada) {

                return [
                    'endereco' => $parada['endereco_formatado'],
                    'latitude' => $parada['latitude'],
                    'longitude' => $parada['longitude'],
                ];
            })->values();
        }

        /*
        |--------------------------------------------------------------------------
        | DESTINO
        |--------------------------------------------------------------------------
        */

        $retorno['destino'] = [
            'endereco' => $destino['endereco_formatado'],
            'latitude' => $destino['latitude'],
            'longitude' => $destino['longitude'],
        ];

        /*
        |--------------------------------------------------------------------------
        | TOTAIS
        |--------------------------------------------------------------------------
        */

        $retorno['distancia_km'] = $rota['distancia_km'];
        $retorno['tempo_minutos'] = $rota['tempo_minutos'];
        return $retorno;
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULAR ROTA
    |--------------------------------------------------------------------------
    */

    private function calcularRota(
        array $origem,
        array $destino,
        array $paradas = []
    ): array {

        /*
        |--------------------------------------------------------------------------
        | WAYPOINTS
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
        | REQUEST
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
                'trechos' => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | LEGS
        |--------------------------------------------------------------------------
        */

        $legs = $data['routes'][0]['legs'];

        $distanciaMetros = 0;

        $tempoSegundos = 0;

        $trechos = [];

        foreach ($legs as $leg) {

            $distanciaMetros += $leg['distance']['value'];

            $tempoSegundos += $leg['duration']['value'];

            $trechos[] = [
                'origem' => $leg['start_address'],
                'destino' => $leg['end_address'],
                'distancia_km' => round(
                    $leg['distance']['value'] / 1000,
                    2
                ),
                'tempo_minutos' => round(
                    $leg['duration']['value'] / 60
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        return [
            'distancia_km' => round(
                $distanciaMetros / 1000,
                2
            ),

            'tempo_minutos' => round(
                $tempoSegundos / 60
            ),

            'trechos' => $trechos,
        ];
    }
}
