<?php

namespace App\Http\Controllers;

use App\Models\ProdutoCategoria;
use Illuminate\Http\Request;

class ProdutoCategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ProdutoCategoria::with('produto')->paginate();
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
    public function show(ProdutoCategoria $produtoCategoria)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProdutoCategoria $produtoCategoria)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProdutoCategoria $produtoCategoria)
    {
        //
    }
}
