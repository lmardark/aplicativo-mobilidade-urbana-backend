<?php

namespace App\Http\Controllers\API;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Laravel\Facades\Image;

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
        $user = User::create([
            "name" => $request->name,
            "data_nascimento" => $request->data_nascimento,
            "type" => $request->type,
            "telefone" => $request->telefone,
            "cpf" => $request->cpf,
            "email" => $request->email,
            "foto" => $request->foto,
            "status" => 'aprovação',
            "password" => bcrypt($request->password)
        ]);

        $image = $request->file('image', null);

        if ($image) {
            $host = $this->SchemeAndHttpHost($request);
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            ]);


            $image = $request->file('image');
            $imageName = $image->getClientOriginalName();
            $imageName = time() . '_' . $imageName;
            $thumbnail = $image->getClientOriginalName();
            $thumbnail = time() . '_thumbnail' . $thumbnail;

            // Versão 4.0 usando o facade
            Image::decode($image)
                ->resize(100, 100)
                ->save(public_path('images/') . $thumbnail);

            $image->move(public_path('images'), $imageName);
            $user->foto = "{$host}/images/{$thumbnail}";
            $user->saveOrFail();
        }

        return response()->json([
            'success' => true,
            'message' => 'Registro realizado com sucesso',
            'data' => $user
        ], 201);
    }

    public function SchemeAndHttpHost($request)
    {
        if (App::environment('local')) {
            return $request->getSchemeAndHttpHost();
        }
        return "https://api.pen6.app/";
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
