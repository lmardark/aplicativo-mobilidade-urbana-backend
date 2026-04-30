<?php

namespace App\Http\Controllers;

use App\Models\Passageiro;
use Illuminate\Http\Request;

class PassageiroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Passageiro::with('user')->paginate();
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
    public function show(Passageiro $passageiro)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Passageiro $passageiro)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Passageiro $passageiro)
    {
        //
    }

    public function passageirosArquivados()
    {
        return  Passageiro::onlyTrashed()
            ->latest('updated_at')
            ->paginate();
    }
}
