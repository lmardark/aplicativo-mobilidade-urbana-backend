<?php

namespace App\Http\Controllers\API;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use GrahamCampbell\ResultType\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $users = User::orderBy('id', 'DESC')->paginate();


        return response()->json([
            'success' => true,
            'message' => __('messages.success.list_record'),
            'data' => $users
        ], 201);
    }

    public function usuarioLogado()
    {
        return Auth::user();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $user = User::create([
            "name" => $request->name,
            "data_nascimento" => $request->data_nascimento,
            "type" => $request->type,
            "telefone" => $request->telefone,
            "cpf" => $request->cpf,
            "email" => $request->email,
            "foto" => $request->foto,
            "status" => 'aprovação',
            "password" => bcrypt($input['password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registro realizado com sucesso',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return User::findOrFail($id);
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
