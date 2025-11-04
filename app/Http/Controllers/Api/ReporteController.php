<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ReporteController extends Controller
{
    public function index(): JsonResponse
    {
        $reportes = Reporte::with('userReporta')->orderBy('created_at', 'desc')->get();
        return response()->json($reportes);
    }

    public function show($id): JsonResponse
    {
        $reporte = Reporte::with('userReporta')->find($id);
        
        if (!$reporte) {
            return response()->json(['message' => 'Reporte no encontrado'], 404);
        }
        
        return response()->json($reporte);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id_reporta' => 'required|exists:users,id',
                'tipo_contenido' => 'required|in:producto,usuario,resena,comentario',
                'id_contenido' => 'required|integer',
                'motivo' => 'required|in:spam,contenido_inapropiado,informacion_falsa,violacion_terminos,otro',
                'descripcion' => 'nullable|string|max:1000',
                'estado' => 'in:pendiente,en_revision,resuelto,rechazado'
            ]);

            // Establecer estado por defecto
            if (!isset($validated['estado'])) {
                $validated['estado'] = 'pendiente';
            }

            $reporte = Reporte::create($validated);
            $reporte->load('userReporta');

            return response()->json($reporte, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $reporte = Reporte::find($id);
        
        if (!$reporte) {
            return response()->json(['message' => 'Reporte no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,en_revision,resuelto,rechazado',
                'descripcion' => 'nullable|string|max:1000'
            ]);

            $reporte->update($validated);
            $reporte->load('userReporta');

            return response()->json($reporte);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $reporte = Reporte::find($id);
        
        if (!$reporte) {
            return response()->json(['message' => 'Reporte no encontrado'], 404);
        }

        $reporte->delete();
        return response()->json(['message' => 'Reporte eliminado correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $reportes = Reporte::where('user_id_reporta', $userId)
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json($reportes);
    }

    public function getByStatus($status): JsonResponse
    {
        $reportes = Reporte::where('estado', $status)
                          ->with('userReporta')
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json($reportes);
    }

    public function getByContent($tipoContenido, $idContenido): JsonResponse
    {
        $reportes = Reporte::where('tipo_contenido', $tipoContenido)
                          ->where('id_contenido', $idContenido)
                          ->with('userReporta')
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json($reportes);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $reporte = Reporte::find($id);
        
        if (!$reporte) {
            return response()->json(['message' => 'Reporte no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,en_revision,resuelto,rechazado'
            ]);

            $reporte->update($validated);
            $reporte->load('userReporta');

            return response()->json($reporte);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function getStats(): JsonResponse
    {
        $stats = [
            'total' => Reporte::count(),
            'pendientes' => Reporte::where('estado', 'pendiente')->count(),
            'en_revision' => Reporte::where('estado', 'en_revision')->count(),
            'resueltos' => Reporte::where('estado', 'resuelto')->count(),
            'rechazados' => Reporte::where('estado', 'rechazado')->count(),
            'por_tipo' => Reporte::selectRaw('tipo_contenido, COUNT(*) as total')
                                ->groupBy('tipo_contenido')
                                ->get(),
            'por_motivo' => Reporte::selectRaw('motivo, COUNT(*) as total')
                                  ->groupBy('motivo')
                                  ->get()
        ];
        
        return response()->json($stats);
    }
}