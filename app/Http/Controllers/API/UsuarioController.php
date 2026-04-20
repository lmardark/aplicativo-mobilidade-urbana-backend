<?php

namespace App\Http\Controllers\API;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        User::create([
            "name" => $request->name,
            "data_nascimento" => $request->data_nascimento,
            "type" => UserType::MOTORISTA,
            "telefone" => $request->telefone,
            "cpf" => $request->cpf,
            "email" => $request->email,
            "foto" => $request->foto_pefil,
            "status" => $request->status,
            "password" => "123"
        ]);
        return 'registro realizado com sucesso';
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
