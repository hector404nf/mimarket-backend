<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use App\Models\DireccionEnvio;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class OrdenController extends Controller
{
    public function index(): JsonResponse
    {
        $ordenes = Orden::with(['user', 'detalles', 'usoCupones', 'envio'])->get();
        return response()->json($ordenes);
    }

    public function show($id): JsonResponse
    {
        $orden = Orden::with(['user', 'detalles.producto', 'usoCupones.cupon', 'envio'])->find($id);
        
        if (!$orden) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }
        
        // Adjuntar la dirección de envío desde la relación envio (snapshot)
        try {
            $direccionPlano = null;
            if ($orden->envio) {
                $env = $orden->envio;
                if (!empty($env->direccion)) {
                    $direccionPlano = $env->direccion;
                    if (!empty($env->ciudad)) {
                        $direccionPlano .= ', ' . $env->ciudad;
                    }
                    if (!empty($env->codigo_postal)) {
                        $direccionPlano .= ' (' . $env->codigo_postal . ')';
                    }
                }

                $orden->setAttribute('direccion_envio_meta', [
                    'nombre_completo' => $env->nombre_completo ?? null,
                    'ciudad' => $env->ciudad ?? null,
                    'estado' => $env->estado ?? null,
                    'codigo_postal' => $env->codigo_postal ?? null,
                    'pais' => $env->pais ?? null,
                    'telefono' => $env->telefono ?? null,
                    'latitud' => $env->latitud ?? null,
                    'longitud' => $env->longitud ?? null,
                ]);
            }

            if ($direccionPlano) {
                $orden->setAttribute('direccion_envio', $direccionPlano);
            }
        } catch (\Throwable $e) {
            \Log::warning('No se pudo adjuntar direccion_envio a la orden', [
                'orden_id' => $orden->id_orden,
                'error' => $e->getMessage(),
            ]);
        }
        
        return response()->json($orden);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'numero_orden' => 'required|string|unique:ordenes,numero_orden',
                'total' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'impuestos' => 'nullable|numeric|min:0',
                'descuentos' => 'nullable|numeric|min:0',
                'costo_envio' => 'nullable|numeric|min:0',
                'estado' => 'required|in:pendiente,procesando,enviado,entregado,cancelado',
                'metodo_pago' => 'required|string',
                'fecha_estimada_entrega' => 'nullable|date',
                // Datos de envío (relación)
                'direccion_envio' => 'nullable|string',
                'id_direccion_envio' => 'nullable|integer|exists:direcciones_envio,id_direccion',
                'nombre_completo' => 'nullable|string',
                'ciudad' => 'nullable|string',
                'estado_envio' => 'nullable|string',
                'codigo_postal' => 'nullable|string',
                'pais' => 'nullable|string',
                'telefono' => 'nullable|string',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
            ]);

            // Crear la orden sin datos de dirección
            $orden = Orden::create(collect($validated)->except([
                'direccion_envio', 'id_direccion_envio', 'nombre_completo', 'ciudad', 'estado_envio', 'codigo_postal', 'pais', 'telefono', 'latitud', 'longitud'
            ])->toArray());

            // Crear relación de envío si se proporciona
            try {
                $envioPayload = [];
                if (!empty($validated['id_direccion_envio'])) {
                    $dir = DireccionEnvio::where('id_direccion', $validated['id_direccion_envio'])
                                          ->where('user_id', $validated['user_id'])
                                          ->first();
                    if ($dir) {
                        $envioPayload = [
                            'id_direccion_envio' => $dir->id_direccion,
                            'nombre_completo' => $dir->nombre_completo,
                            'direccion' => $dir->direccion,
                            'ciudad' => $dir->ciudad,
                            'estado' => $dir->estado,
                            'codigo_postal' => $dir->codigo_postal,
                            'pais' => $dir->pais,
                            'telefono' => $dir->telefono,
                            'latitud' => $validated['latitud'] ?? null,
                            'longitud' => $validated['longitud'] ?? null,
                        ];
                    }
                }
                if (empty($envioPayload)) {
                    $envioPayload = [
                        'nombre_completo' => $validated['nombre_completo'] ?? null,
                        'direccion' => $validated['direccion_envio'] ?? null,
                        'ciudad' => $validated['ciudad'] ?? null,
                        'estado' => $validated['estado_envio'] ?? null,
                        'codigo_postal' => $validated['codigo_postal'] ?? null,
                        'pais' => $validated['pais'] ?? null,
                        'telefono' => $validated['telefono'] ?? null,
                        'latitud' => $validated['latitud'] ?? null,
                        'longitud' => $validated['longitud'] ?? null,
                    ];
                }
                OrdenEnvio::create(array_merge(['id_orden' => $orden->id_orden], $envioPayload));
            } catch (\Throwable $e) {
                \Log::warning('No se pudo crear relacion de envio en store de Orden', ['error' => $e->getMessage()]);
            }

            $orden->load(['user', 'detalles', 'usoCupones', 'envio']);

            return response()->json($orden, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $orden = Orden::with(['detalles.producto'])->find($id);
        
        if (!$orden) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'estado' => 'sometimes|in:pendiente,procesando,enviado,entregado,cancelado',
                'fecha_estimada_entrega' => 'nullable|date',
                'notas' => 'nullable|string'
            ]);

            $estadoAnterior = $orden->estado;
            $orden->update($validated);

            // Si se cancela la orden y antes no estaba cancelada, reponer stock
            if (($validated['estado'] ?? null) === 'cancelado' && $estadoAnterior !== 'cancelado') {
                foreach ($orden->detalles as $detalle) {
                    if ($detalle->producto) {
                        $detalle->producto->increment('cantidad_stock', $detalle->cantidad);
                    }
                }
            }

            $orden->load(['user', 'detalles', 'usoCupones']);

            return response()->json($orden);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $orden = Orden::with(['detalles.producto'])->find($id);
        
        if (!$orden) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        if ($orden->estado !== 'pendiente') {
            return response()->json(['message' => 'Solo se pueden eliminar órdenes pendientes'], 400);
        }

        // Reponer stock de los productos asociados antes de eliminar la orden
        foreach ($orden->detalles as $detalle) {
            if ($detalle->producto) {
                $detalle->producto->increment('cantidad_stock', $detalle->cantidad);
            }
        }

        $orden->delete();
        return response()->json(['message' => 'Orden eliminada correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $ordenes = Orden::where('user_id', $userId)
                       ->with(['detalles.producto', 'usoCupones.cupon', 'envio'])
                       ->orderBy('created_at', 'desc')
                       ->get();
        
        return response()->json($ordenes);
    }

    /**
     * Obtener órdenes por tienda (filtrando por los productos de la orden).
     */
    public function getByTienda($tiendaId): JsonResponse
    {
        $ordenes = Orden::with(['user', 'detalles.producto', 'usoCupones.cupon', 'envio'])
            ->whereHas('detalles.producto', function ($q) use ($tiendaId) {
                $q->where('id_tienda', $tiendaId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($ordenes);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $orden = Orden::find($id);
        
        if (!$orden) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'estado' => 'required|in:pendiente,procesando,enviado,entregado,cancelado',
                'motivo' => 'nullable|string|max:255'
            ]);

            $orden->update($validated);
            $orden->load(['user', 'detalles', 'usoCupones']);

            // Crear notificación al cliente por cambio de estado
            try {
                $estado = $validated['estado'];
                $mensajes = [
                    'pendiente'   => ['titulo' => 'Pedido pendiente',   'mensaje' => 'Tu pedido está pendiente y pronto será procesado.',    'tipo' => 'pedido_pendiente'],
                    'procesando'  => ['titulo' => 'Pedido en preparación','mensaje' => 'Tu pedido está siendo preparado por la tienda.',      'tipo' => 'pedido_procesando'],
                    'enviado'     => ['titulo' => 'Pedido enviado',     'mensaje' => 'Tu pedido ha sido enviado. Está en camino.',          'tipo' => 'pedido_enviado'],
                    'entregado'   => ['titulo' => 'Pedido entregado',   'mensaje' => 'Tu pedido ha sido entregado. ¡Gracias por tu compra!', 'tipo' => 'pedido_entregado'],
                    'cancelado'   => ['titulo' => 'Pedido cancelado',   'mensaje' => 'Tu pedido ha sido cancelado.',                         'tipo' => 'pedido_cancelado'],
                ];

                $def = $mensajes[$estado] ?? null;
                if ($def) {
                    $numero = $orden->numero_orden ?? ('ORD-' . $orden->id_orden);
                    $mensaje = $def['mensaje'] . " #$numero";
                    // Incluir motivo cuando el estado sea cancelado y exista motivo
                    $motivo = trim((string)($validated['motivo'] ?? ''));
                    if ($estado === 'cancelado' && $motivo !== '') {
                        $mensaje .= " | Motivo: " . $motivo;
                    }

                    Notificacion::create([
                        'user_id'    => $orden->user_id,
                        'tipo'       => $def['tipo'],
                        'titulo'     => $def['titulo'],
                        'mensaje'    => $mensaje,
                        'url_accion' => "/pedidos/{$orden->id_orden}",
                        'leida'      => false,
                    ]);
                }
            } catch (\Throwable $e) {
                // No interrumpir el flujo si falla la notificación
                \Log::warning('No se pudo crear la notificación de cambio de estado', [
                    'orden_id' => $orden->id_orden ?? $id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json($orden);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }
}