<?php

namespace App\Http\Controllers;

use App\Models\ProdutosCorrida;
use Illuminate\Http\Request;

class ProdutosCorridaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ProdutosCorrida::paginate();
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
    public function show(ProdutosCorrida $ProdutosCorrida)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProdutosCorrida $ProdutosCorrida)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProdutosCorrida $ProdutosCorrida)
    {
        //
    }
}
