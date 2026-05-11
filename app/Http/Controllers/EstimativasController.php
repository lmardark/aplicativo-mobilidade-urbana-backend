<?php

namespace App\Http\Controllers;

use App\Services\EstimarRotaService;

class EstimativasController extends Controller
{
    public function __construct(
        protected EstimarRotaService $estimarRotaService
    ) {}

    /**
     * Display a listing of the resource.
     */



    public function estimarRota()
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
        return $this->estimarRotaService->executar(enderecos: $enderecos);
    }
}
