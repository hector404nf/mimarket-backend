<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleOrden;
use Illuminate\Http\JsonResponse;

class ClienteController extends Controller
{
    /**
     * Obtener clientes por tienda con agregación (pedidos, gasto, último/primer pedido).
     */
    public function getByTienda($tiendaId): JsonResponse
    {
        $detalles = DetalleOrden::with(['orden.user', 'orden.envio', 'producto'])
            ->whereHas('producto', function ($q) use ($tiendaId) {
                $q->where('id_tienda', $tiendaId);
            })
            ->get();

        $clientes = [];

        foreach ($detalles as $det) {
            $orden = $det->orden;
            if (!$orden || !$orden->user) {
                continue;
            }

            $uid = $orden->user->id;
            if (!isset($clientes[$uid])) {
                $clientes[$uid] = [
                    'id' => $uid,
                    'nombre' => trim(($orden->user->name ?? '') . ' ' . ($orden->user->apellido ?? '')),
                    'email' => $orden->user->email,
                    'telefono' => $orden->user->telefono,
                    'avatar' => $orden->user->foto_perfil,
                    'fechaRegistro' => $orden->created_at,
                    'totalPedidos' => 0,
                    'totalGastado' => 0.0,
                    'ultimoPedido' => $orden->created_at,
                    'estado' => 'activo',
                    'direccion' => $this->direccionPlano($orden->envio),
                    '_ordenIds' => [],
                ];
            }

            // Contar pedido único por usuario (evitar duplicados por múltiples detalles de un mismo pedido)
            if (!in_array($orden->id_orden, $clientes[$uid]['_ordenIds'])) {
                $clientes[$uid]['_ordenIds'][] = $orden->id_orden;
                $clientes[$uid]['totalPedidos'] += 1;
                // Actualizar primera compra
                if ($orden->created_at < $clientes[$uid]['fechaRegistro']) {
                    $clientes[$uid]['fechaRegistro'] = $orden->created_at;
                }
                // Actualizar último pedido y dirección
                if ($orden->created_at > $clientes[$uid]['ultimoPedido']) {
                    $clientes[$uid]['ultimoPedido'] = $orden->created_at;
                    $clientes[$uid]['direccion'] = $this->direccionPlano($orden->envio) ?? $clientes[$uid]['direccion'];
                }
            }

            // Sumar gasto del detalle (subtotal específico de la tienda)
            $clientes[$uid]['totalGastado'] += (float) $det->subtotal;
        }

        // Calcular estado VIP/activo/inactivo
        $sixMonthsAgo = now()->subMonths(6);
        $resultado = [];
        foreach ($clientes as $c) {
            $estado = 'activo';
            if (($c['totalPedidos'] ?? 0) >= 5 || ($c['totalGastado'] ?? 0) >= 50000000) {
                $estado = 'vip';
            }
            if ($c['ultimoPedido'] < $sixMonthsAgo) {
                $estado = 'inactivo';
            }
            $resultado[] = [
                'id' => $c['id'],
                'nombre' => $c['nombre'],
                'email' => $c['email'],
                'telefono' => $c['telefono'],
                'avatar' => $c['avatar'],
                'fechaRegistro' => $c['fechaRegistro'],
                'totalPedidos' => $c['totalPedidos'],
                'totalGastado' => $c['totalGastado'],
                'ultimoPedido' => $c['ultimoPedido'],
                'estado' => $estado,
                'direccion' => $c['direccion'] ?? null,
            ];
        }

        // Ordenar por último pedido descendente
        usort($resultado, function ($a, $b) {
            return strtotime((string) $b['ultimoPedido']) <=> strtotime((string) $a['ultimoPedido']);
        });

        return response()->json($resultado);
    }

    private function direccionPlano($envio): ?string
    {
        if (!$envio) return null;
        $direccion = $envio->direccion ?? null;
        if (!empty($envio->ciudad)) {
            $direccion = $direccion ? ($direccion . ', ' . $envio->ciudad) : $envio->ciudad;
        }
        if (!empty($envio->codigo_postal)) {
            $direccion = $direccion ? ($direccion . ' (' . $envio->codigo_postal . ')') : $envio->codigo_postal;
        }
        return $direccion;
    }
}