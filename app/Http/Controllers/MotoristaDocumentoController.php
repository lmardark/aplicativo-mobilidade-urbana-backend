<?php

namespace App\Http\Controllers;

use App\Models\MotoristaDocumento;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class MotoristaDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MotoristaDocumento::paginate();
    }

    /**
     * Store a newly created resource in storage.
     */



    public function store(Request $request)
    {
        $request->validate([
            'motorista_id' => 'required|integer',
            'documento' => 'required|string',
            'arquivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048', // 2MB
        ]);

        $file = $request->file('arquivo');

        // Nome único
        $fileName = time() . '_' . $file->getClientOriginalName();

        // Salvar arquivo
        $path = $file->storeAs('motorista_documentos', $fileName);

        // Salvar no banco
        $documento = MotoristaDocumento::create([
            'motorista_id' => $request->motorista_id,
            'documento' => $request->documento,
            'name' => $file->getClientOriginalName(),
            'type' => $file->extension(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'status' => 'em_analise',
        ]);

        return response()->json([
            'message' => 'Arquivo enviado com sucesso',
            'data' => $documento
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($motoristaDocumentoId)
    {
        return MotoristaDocumento::where('motorista_id', $motoristaDocumentoId)->paginate();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MotoristaDocumento $motoristaDocumento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $motoristaDocumentoId)
    {
        $documento = MotoristaDocumento::find($motoristaDocumentoId);

        if (!$documento) {
            return response()->json([
                'message' => 'Documento não encontrado'
            ], 404);
        }

        // Verifica e deleta o arquivo no storage PRIVATE (local)
        if ($documento->path && Storage::disk('local')->exists($documento->path)) {
            Storage::disk('local')->delete($documento->path);
        }

        // Remove do banco (soft delete)
        $documento->delete();

        return response()->json([
            'message' => 'Documento removido com sucesso'
        ]);
    }
}
