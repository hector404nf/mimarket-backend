<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use App\Models\DetalleOrden;
use App\Models\Carrito;
use App\Models\Producto;
use App\Models\Cupon;
use App\Models\UsoCupon;
use App\Models\DireccionEnvio;
use App\Models\OrdenEnvio;
use App\Models\Notificacion;
use App\Services\ComisionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    protected $comisionService;

    public function __construct(ComisionService $comisionService)
    {
        $this->comisionService = $comisionService;
    }
    public function processCheckout(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'metodo_pago' => 'required|string|in:tarjeta,efectivo,transferencia',
                // Ahora la dirección puede venir como id de DireccionEnvio o como string de respaldo
                'id_direccion_envio' => 'nullable|integer|exists:direcciones_envio,id_direccion',
                'direccion_envio' => 'nullable|string',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
                'notas' => 'nullable|string',
                'codigo_cupon' => 'nullable|string|exists:cupones,codigo'
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            // Obtener items del carrito
            $itemsCarrito = Carrito::where('user_id', $user->id)
                                  ->with('producto')
                                  ->get();

            if ($itemsCarrito->isEmpty()) {
                return response()->json(['message' => 'El carrito está vacío'], 400);
            }

            // Verificar disponibilidad de productos usando la columna real cantidad_stock
            foreach ($itemsCarrito as $item) {
                if ($item->producto->cantidad_stock < $item->cantidad) {
                    return response()->json([
                        'message' => "Stock insuficiente para el producto: {$item->producto->nombre}",
                        'producto' => $item->producto->nombre,
                        'stock_disponible' => $item->producto->cantidad_stock,
                        'cantidad_solicitada' => $item->cantidad
                    ], 400);
                }
            }

            return DB::transaction(function () use ($validated, $user, $itemsCarrito) {
                // Calcular totales
                $subtotal = 0;
                foreach ($itemsCarrito as $item) {
                    $precio = $item->producto->precio_oferta ?? $item->producto->precio;
                    $subtotal += $precio * $item->cantidad;
                }

                $impuestos = $subtotal * 0.19; // IVA 19%
                $costo_envio = $subtotal > 50000 ? 0 : 5000; // Envío gratis sobre $50,000
                $descuento = 0;

                // Aplicar cupón si existe
                $cupon = null;
                if (!empty($validated['codigo_cupon'])) {
                    $cupon = Cupon::where('codigo', $validated['codigo_cupon'])
                                  ->where('activo', true)
                                  ->where('fecha_inicio', '<=', now())
                                  ->where('fecha_fin', '>=', now())
                                  ->first();

                    if ($cupon) {
                        if ($cupon->monto_minimo && $subtotal < $cupon->monto_minimo) {
                            throw new \Exception("El monto mínimo para este cupón es $" . number_format($cupon->monto_minimo, 0));
                        }

                        if ($cupon->tipo === 'porcentaje') {
                            $descuento = ($subtotal * $cupon->valor) / 100;
                            if ($cupon->monto_maximo && $descuento > $cupon->monto_maximo) {
                                $descuento = $cupon->monto_maximo;
                            }
                        } else {
                            $descuento = $cupon->valor;
                        }
                    }
                }

                $total = $subtotal + $impuestos + $costo_envio - $descuento;

                // Generar número de orden único
                $numero_orden = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                while (Orden::where('numero_orden', $numero_orden)->exists()) {
                    $numero_orden = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                }

                // Preparar datos de dirección de envío (snapshot)
                $direccion = null;
                if (!empty($validated['id_direccion_envio'])) {
                    $direccion = DireccionEnvio::where('id_direccion', $validated['id_direccion_envio'])
                                                ->where('user_id', $user->id)
                                                ->first();
                }

                if (!$direccion) {
                    $direccion = DireccionEnvio::where('user_id', $user->id)
                                               ->orderByDesc('predeterminada')
                                               ->orderByDesc('created_at')
                                               ->first();
                }

                // Crear la orden
                $orden = Orden::create([
                    'user_id' => $user->id,
                    'numero_orden' => $numero_orden,
                    'total' => $total,
                    'subtotal' => $subtotal,
                    'impuestos' => $impuestos,
                    'costo_envio' => $costo_envio,
                    'estado' => 'pendiente',
                    'metodo_pago' => $validated['metodo_pago'],
                    'estado_pago' => 'pendiente',
                    'notas' => $validated['notas'] ?? null
                ]);

                // Crear snapshot de dirección en tabla relacionada
                if ($direccion) {
                    OrdenEnvio::create([
                        'id_orden' => $orden->id_orden,
                        'id_direccion_envio' => $direccion->id_direccion,
                        'nombre_completo' => $direccion->nombre_completo,
                        'direccion' => $direccion->direccion,
                        'ciudad' => $direccion->ciudad,
                        'estado' => $direccion->estado,
                        'codigo_postal' => $direccion->codigo_postal,
                        'pais' => $direccion->pais,
                        'telefono' => $direccion->telefono,
                        'latitud' => $validated['latitud'] ?? null,
                        'longitud' => $validated['longitud'] ?? null,
                    ]);
                } elseif (!empty($validated['direccion_envio'])) {
                    OrdenEnvio::create([
                        'id_orden' => $orden->id_orden,
                        'direccion' => $validated['direccion_envio'],
                        'latitud' => $validated['latitud'] ?? null,
                        'longitud' => $validated['longitud'] ?? null,
                    ]);
                }

                // Crear detalles de la orden y actualizar stock
                foreach ($itemsCarrito as $item) {
                    $precio = $item->producto->precio_oferta ?? $item->producto->precio;
                    
                    DetalleOrden::create([
                        'id_orden' => $orden->id_orden,
                        'id_producto' => $item->id_producto,
                        'cantidad' => $item->cantidad,
                        'precio_unitario' => $precio,
                        'subtotal' => $precio * $item->cantidad
                    ]);

                    // Actualizar stock del producto en la columna correcta
                    $item->producto->decrement('cantidad_stock', $item->cantidad);
                }

                // Registrar uso del cupón si existe
                if ($cupon) {
                    UsoCupon::create([
                        'id_cupon' => $cupon->id_cupon,
                        'user_id' => $user->id,
                        'id_orden' => $orden->id_orden,
                        'descuento_aplicado' => $descuento
                    ]);

                    // Incrementar contador de usos del cupón
                    $cupon->increment('usos_actuales');
                }

                // Limpiar el carrito
                Carrito::where('user_id', $user->id)->delete();

                // Crear notificaciones automáticas
                $this->crearNotificacionesOrden($orden, $itemsCarrito);

                // Calcular comisiones para la orden
                try {
                    $comisionesCalculadas = $this->comisionService->calcularComisionesOrden($orden);
                    if ($comisionesCalculadas) {
                        Log::info("Comisiones calculadas exitosamente para la orden {$orden->id_orden}");
                    } else {
                        Log::warning("No se pudieron calcular las comisiones para la orden {$orden->id_orden}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error calculando comisiones para orden {$orden->id_orden}: " . $e->getMessage());
                    // No interrumpir el proceso de checkout por errores en comisiones
                }

                // Cargar relaciones para la respuesta
                $orden->load(['user', 'detalles.producto', 'usoCupones.cupon', 'envio']);

                return response()->json([
                    'message' => 'Orden creada exitosamente',
                    'orden' => $orden,
                    'resumen' => [
                        'subtotal' => $subtotal,
                        'impuestos' => $impuestos,
                        'costo_envio' => $costo_envio,
                        'descuento' => $descuento,
                        'total' => $total
                    ]
                ], 201);
            });

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function calculateTotals(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'codigo_cupon' => 'nullable|string|exists:cupones,codigo'
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            // Obtener items del carrito
            $itemsCarrito = Carrito::where('user_id', $user->id)
                                  ->with('producto')
                                  ->get();

            if ($itemsCarrito->isEmpty()) {
                return response()->json(['message' => 'El carrito está vacío'], 400);
            }

            // Calcular subtotal
            $subtotal = 0;
            foreach ($itemsCarrito as $item) {
                $precio = $item->producto->precio_oferta ?? $item->producto->precio;
                $subtotal += $precio * $item->cantidad;
            }

            $impuestos = $subtotal * 0.19; // IVA 19%
            $costo_envio = $subtotal > 50000 ? 0 : 5000; // Envío gratis sobre $50,000
            $descuento = 0;
            $cupon_aplicado = null;

            // Verificar cupón si existe
            if (!empty($validated['codigo_cupon'])) {
                $cupon = Cupon::where('codigo', $validated['codigo_cupon'])
                              ->where('activo', true)
                              ->where('fecha_inicio', '<=', now())
                              ->where('fecha_fin', '>=', now())
                              ->first();

                if ($cupon) {
                    if ($cupon->monto_minimo && $subtotal < $cupon->monto_minimo) {
                        return response()->json([
                            'message' => "El monto mínimo para este cupón es $" . number_format($cupon->monto_minimo, 0),
                            'cupon_valido' => false
                        ], 400);
                    }

                    if ($cupon->tipo === 'porcentaje') {
                        $descuento = ($subtotal * $cupon->valor) / 100;
                        if ($cupon->monto_maximo && $descuento > $cupon->monto_maximo) {
                            $descuento = $cupon->monto_maximo;
                        }
                    } else {
                        $descuento = $cupon->valor;
                    }

                    $cupon_aplicado = [
                        'codigo' => $cupon->codigo,
                        'descripcion' => $cupon->descripcion,
                        'tipo' => $cupon->tipo,
                        'valor' => $cupon->valor,
                        'descuento_aplicado' => $descuento
                    ];
                } else {
                    return response()->json([
                        'message' => 'Cupón no válido o expirado',
                        'cupon_valido' => false
                    ], 400);
                }
            }

            $total = $subtotal + $impuestos + $costo_envio - $descuento;

            return response()->json([
                'subtotal' => $subtotal,
                'impuestos' => $impuestos,
                'costo_envio' => $costo_envio,
                'descuento' => $descuento,
                'total' => $total,
                'cupon_aplicado' => $cupon_aplicado,
                'items_count' => $itemsCarrito->count()
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Crear notificaciones automáticas para una orden
     */
    private function crearNotificacionesOrden($orden, $itemsCarrito)
    {
        try {
            // Obtener las tiendas involucradas en la orden
            $tiendasInvolucradas = $itemsCarrito->pluck('producto.id_tienda')->unique();
            
            foreach ($tiendasInvolucradas as $idTienda) {
                // Obtener el propietario de la tienda
                $tienda = \App\Models\Tienda::with('user')->find($idTienda);
                
                if ($tienda && $tienda->user) {
                    // Obtener productos de esta tienda en la orden
                    $productosDeEstaTienda = $itemsCarrito->filter(function($item) use ($idTienda) {
                        return $item->producto->id_tienda == $idTienda;
                    });
                    
                    $totalProductos = $productosDeEstaTienda->sum('cantidad');
                    $totalMonto = $productosDeEstaTienda->sum(function($item) {
                        $precio = $item->producto->precio_oferta ?? $item->producto->precio;
                        return $precio * $item->cantidad;
                    });
                    
                    // Crear notificación para el propietario de la tienda
                    Notificacion::create([
                        'user_id' => $tienda->user->id,
                        'tipo' => 'nuevo_pedido',
                        'titulo' => 'Nuevo pedido recibido',
                        'mensaje' => "Has recibido un nuevo pedido #{$orden->numero_orden} con {$totalProductos} producto(s) por un total de " . number_format($totalMonto, 0) . " Gs.",
                        'url_accion' => "/dashboard-tienda/pedidos/{$orden->id_orden}",
                        'leida' => false
                    ]);
                }
            }
            
            // Crear notificación para el cliente
            Notificacion::create([
                'user_id' => $orden->user_id,
                'tipo' => 'pedido_confirmado',
                'titulo' => 'Pedido confirmado',
                'mensaje' => "Tu pedido #{$orden->numero_orden} ha sido confirmado y está siendo procesado. Total: " . number_format($orden->total, 0) . " Gs.",
                'url_accion' => "/pedidos/{$orden->id_orden}",
                'leida' => false
            ]);
            
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el proceso de checkout
            \Log::error('Error creando notificaciones de orden: ' . $e->getMessage());
        }
    }
}