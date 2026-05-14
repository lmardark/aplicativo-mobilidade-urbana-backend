<?php

namespace App\Http\Controllers\API;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\File;
use phpDocumentor\Reflection\Types\Integer;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $users = User::orderBy('id', 'DESC')->paginate();
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
            "telefone" => $request->telefone,
            "cpf" => $request->cpf,
            "email" => $request->email,
            "foto" => $request->foto,
            "status" => 'ativo',
            "password" => bcrypt($request->password)
        ]);

        $this->criarImagemPefil($request, $user);

        // gera token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token
        ], 201);
    }

    public function register(Request $request)
    {
        $user = User::create([
            "name" => $request->name,
            "data_nascimento" => $request->data_nascimento,
            "telefone" => $request->telefone,
            "cpf" => $request->cpf,
            "email" => $request->email,
            "status" => 'ativo',
            "password" => bcrypt($request->password)
        ]);

        $this->criarImagemPefil($request, $user);

        // gera token JWT
        $token = auth('jwt')->login($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token
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

    public function criarImagemPefil($request, $user)
    {
        $image = $request->file('image', null);

        if ($image) {

            $host = App::environment('local') ? $request->getSchemeAndHttpHost() : "https://api.producao.app/";
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            ]);

            $image = $request->file('image');
            $imageName = $image->getClientOriginalName();
            $imageName = time() . '_' . $imageName;
            $thumbnail = $image->getClientOriginalName();
            $thumbnail = time() . '_thumbnail' . $thumbnail;

            Image::decode($image)
                ->resize(100, 100)
                ->save(public_path('images/') . $thumbnail);

            $image->move(public_path('images'), $imageName);
            $user->foto = "{$host}/images/{$imageName}";
            $user->foto_thumbnail = "{$host}/images/{$thumbnail}";
            $user->saveOrFail();
        }

        return response()->json([
            'success' => true,
            'message' => 'Registro realizado com sucesso',
            'data' => $user
        ], 201);
    }

    public function removerFotoPerfil(string $id)
    {
        try {
            $user = User::findOrFail($id);

            if ($user->foto) {
                if ($user->foto) {
                    $hostImagePath = public_path(str_replace(url('/'), '', $user->foto));
                    if (File::exists($hostImagePath)) {
                        File::delete($hostImagePath);
                    }
                }
                if ($user->foto_thumbnail) {
                    $hostThumbPath = public_path(str_replace(url('/'), '', $user->foto_thumbnail));
                    if (File::exists($hostThumbPath)) {
                        File::delete($hostThumbPath);
                    }
                }
            }

            $user->foto = null;
            $user->foto_thumbnail = null;
            $user->saveOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Foto removida com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover foto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function alterarFotoPerfil(Request $request, string $id)
    {
        try {
            $user = User::findOrFail($id);
            $this->removerFotoPerfil($user);
            $this->criarImagemPefil($request, $user);
            return response()->json([
                'success' => true,
                'message' => 'Foto alterada com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar foto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function usuarioArquivar(Request $request)
    {
        try {
            $usuarios = $request->usuarios ?? [];
            foreach ($usuarios as $usuario) {
                User::findOrFail($usuario['id'])->delete();
            }
            return response()->json([
                'success' => true,
                'message' => 'Usuário arquiva com  sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao alterar foto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function usuariosArquivados()
    {
        return $users = User::onlyTrashed()
            ->latest('updated_at')
            ->paginate();
    }
}
