<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\TiendaController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\CarritoController;
use App\Http\Controllers\Api\OrdenController;
use App\Http\Controllers\Api\DetalleOrdenController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DireccionEnvioController;
use App\Http\Controllers\Api\BusquedaController;
use App\Http\Controllers\Api\ResenaController;
use App\Http\Controllers\Api\FavoritoController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\CuponController;
use App\Http\Controllers\Api\UsoCuponController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\MensajeController;
use App\Http\Controllers\Api\LogActividadController;
use App\Http\Controllers\Api\ComisionController;
use App\Http\Controllers\Api\DashboardAdminController;
use App\Http\Controllers\Api\DashboardTiendaController;
use App\Http\Controllers\Api\RoutingController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\OrdenTrackingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ResenaLikeController;
use App\Http\Controllers\Api\ResenaRespuestaController;
use App\Http\Controllers\Api\MetodoPagoController;
use App\Http\Controllers\Api\HorarioTiendaController;
use App\Http\Controllers\Api\DireccionEnvioTiendaController;
use App\Http\Controllers\Api\TiendaMetodoPagoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas
Route::prefix('v1')->group(function () {
    
    // Rutas para categorías (solo lectura pública)
    Route::get('categorias', [CategoriaController::class, 'index']);
    Route::get('categorias/{categoria}', [CategoriaController::class, 'show']);
    
    // Ruta de prueba simple
Route::get('test', function() {
    return response()->json(['message' => 'API funcionando']);
});

    // Rutas para productos (solo lectura pública)
    Route::get('productos', [ProductoController::class, 'index']);
    Route::get('productos/{producto}', [ProductoController::class, 'show']);
    Route::get('productos/categoria/{categoria}', [ProductoController::class, 'getByCategoria']);
    Route::get('productos/tienda/{tienda}', [ProductoController::class, 'getByTienda']);
    
    // Reseñas agregadas por tienda (lectura pública)
    Route::get('resenas/tienda/{tiendaId}', [ResenaController::class, 'getByTienda']);
    Route::get('resenas/tienda/{tiendaId}/stats', [ResenaController::class, 'getTiendaStats']);
    
    // Rutas para tiendas (solo lectura pública)
    Route::get('tiendas', [TiendaController::class, 'index']);
    Route::get('tiendas/{tienda}', [TiendaController::class, 'show']);
    Route::get('tiendas/{tienda}/productos', [TiendaController::class, 'getProducts']);
    Route::get('tiendas/usuario/{usuario}', [TiendaController::class, 'getByUsuario']);
    Route::post('tiendas/{tienda}/toggle-status', [TiendaController::class, 'toggleStatus']);

    // Routing (público): proxy OSRM
    Route::get('routing/driving', [RoutingController::class, 'driving']);

    // Push: clave pública VAPID (público)
    Route::get('push/vapid-public-key', [PushController::class, 'getVapidPublicKey']);


    
    
    
    
    
    // Rutas para detalles de orden
    Route::apiResource('detalle-ordenes', DetalleOrdenController::class);
    Route::get('detalle-ordenes/orden/{orden}', [DetalleOrdenController::class, 'getByOrden']);
    

    
    // Rutas para búsquedas
    Route::apiResource('busquedas', BusquedaController::class);
    Route::get('busquedas/usuario/{usuario}', [BusquedaController::class, 'getByUser']);
    Route::get('busquedas/populares', [BusquedaController::class, 'getPopularSearches']);
    Route::get('busquedas/recientes/{usuario}', [BusquedaController::class, 'getRecentSearches']);
    
    // Rutas para reseñas
    Route::apiResource('resenas', ResenaController::class);
    Route::get('resenas/producto/{producto}', [ResenaController::class, 'getByProducto']);
    Route::get('resenas/usuario/{usuario}', [ResenaController::class, 'getByUser']);
    Route::get('resenas/producto/{producto}/stats', [ResenaController::class, 'getProductoStats']);
    Route::patch('resenas/{resena}/verify', [ResenaController::class, 'verify']);
    // Likes de reseñas
    Route::get('resenas/{resena}/likes', [ResenaLikeController::class, 'getByResena']);
    Route::get('resenas/{resena}/likes/check/{usuario}', [ResenaLikeController::class, 'check']);
    Route::post('resenas/likes/toggle', [ResenaLikeController::class, 'toggle']);
    // Respuestas de reseñas
    Route::get('resenas/{resena}/respuestas', [ResenaRespuestaController::class, 'getByResena']);
    Route::post('resenas/{resena}/respuestas', [ResenaRespuestaController::class, 'store']);
    Route::patch('resenas/respuestas/{respuesta}', [ResenaRespuestaController::class, 'update']);
    Route::delete('resenas/respuestas/{respuesta}', [ResenaRespuestaController::class, 'destroy']);
    
    // Rutas para favoritos
    Route::apiResource('favoritos', FavoritoController::class);
    Route::get('favoritos/usuario/{usuario}', [FavoritoController::class, 'getByUser']);
    Route::post('favoritos/toggle', [FavoritoController::class, 'toggle']);
    Route::delete('favoritos/producto/{producto}', [FavoritoController::class, 'removeByProduct']);
    Route::get('favoritos/check/{usuario}/{producto}', [FavoritoController::class, 'checkFavorite']);
    
    // Rutas para notificaciones
    Route::apiResource('notificaciones', NotificacionController::class);
    Route::get('notificaciones/usuario/{usuario}', [NotificacionController::class, 'getByUser']);
    Route::get('notificaciones/tienda/{tienda}', [NotificacionController::class, 'getByTienda']);
    Route::patch('notificaciones/{notificacion}/read', [NotificacionController::class, 'markAsRead']);
    Route::patch('notificaciones/usuario/{usuario}/read-all', [NotificacionController::class, 'markAllAsRead']);
    Route::get('notificaciones/usuario/{usuario}/unread-count', [NotificacionController::class, 'getUnreadCount']);
    Route::get('notificaciones/usuario/{usuario}/unread', [NotificacionController::class, 'getUnread']);
    Route::delete('notificaciones/usuario/{usuario}/read', [NotificacionController::class, 'deleteAllRead']);
    
    // Rutas para cupones
    Route::apiResource('cupones', CuponController::class);
    Route::post('cupones/validate', [CuponController::class, 'validateCoupon']);
    Route::get('cupones/activos', [CuponController::class, 'getActiveCoupons']);
    Route::patch('cupones/{cupon}/toggle-status', [CuponController::class, 'toggleStatus']);
    Route::get('cupones/{cupon}/usage-stats', [CuponController::class, 'getUsageStats']);
    
    // Rutas para uso de cupones
    Route::apiResource('uso-cupones', UsoCuponController::class);
    Route::get('uso-cupones/cupon/{cupon}', [UsoCuponController::class, 'getByCupon']);
    Route::get('uso-cupones/usuario/{usuario}', [UsoCuponController::class, 'getByUser']);
    Route::get('uso-cupones/orden/{orden}', [UsoCuponController::class, 'getByOrden']);
    
    // Rutas para reportes
    Route::apiResource('reportes', ReporteController::class);
    Route::get('reportes/usuario/{usuario}', [ReporteController::class, 'getByUser']);
    Route::get('reportes/status/{status}', [ReporteController::class, 'getByStatus']);
    Route::get('reportes/contenido/{tipo}', [ReporteController::class, 'getByContent']);
    Route::patch('reportes/{reporte}/status', [ReporteController::class, 'updateStatus']);
    Route::get('reportes/stats', [ReporteController::class, 'getStats']);
    
    // Rutas para mensajes
    Route::apiResource('mensajes', MensajeController::class);
    Route::get('mensajes/usuario/{usuario}/inbox', [MensajeController::class, 'getInbox']);
    Route::get('mensajes/usuario/{usuario}/sent', [MensajeController::class, 'getSent']);
    Route::get('mensajes/conversacion/{usuario1}/{usuario2}', [MensajeController::class, 'getConversation']);
    Route::patch('mensajes/{mensaje}/read', [MensajeController::class, 'markAsRead']);
    Route::patch('mensajes/usuario/{usuario}/read-all', [MensajeController::class, 'markAllAsRead']);
    Route::get('mensajes/usuario/{usuario}/unread-count', [MensajeController::class, 'getUnreadCount']);
    Route::get('mensajes/usuario/{usuario}/unread', [MensajeController::class, 'getUnread']);
    Route::get('mensajes/usuario/{usuario}/contacts', [MensajeController::class, 'getContacts']);
    
    // Rutas para logs de actividad
    Route::apiResource('logs-actividad', LogActividadController::class);
    Route::get('logs-actividad/usuario/{usuario}', [LogActividadController::class, 'getByUser']);
    Route::get('logs-actividad/accion/{accion}', [LogActividadController::class, 'getByAction']);
    Route::get('logs-actividad/tabla/{tabla}', [LogActividadController::class, 'getByTable']);
    Route::get('logs-actividad/fecha/{desde}/{hasta}', [LogActividadController::class, 'getByDateRange']);
    Route::get('logs-actividad/stats', [LogActividadController::class, 'getStats']);
    Route::delete('logs-actividad/clear-old/{dias}', [LogActividadController::class, 'clearOldLogs']);
    
    // Rutas protegidas que requieren autenticación
    Route::middleware('auth:sanctum')->group(function () {
        // Push: suscripciones del usuario autenticado
        Route::get('push/subscriptions', [PushController::class, 'index']);
        Route::post('push/subscriptions', [PushController::class, 'subscribe']);
        Route::delete('push/subscriptions', [PushController::class, 'unsubscribe']);
        Route::post('push/send-test', [PushController::class, 'sendTest']);
        // Rutas para categorías que requieren autenticación
        Route::post('categorias', [CategoriaController::class, 'store']);
        Route::put('categorias/{categoria}', [CategoriaController::class, 'update']);
        Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy']);
        
        // Rutas para usuarios (requieren autenticación)
        Route::apiResource('usuarios', UsuarioController::class);
        Route::post('usuarios/{usuario}/toggle-status', [UsuarioController::class, 'toggleStatus']);
        Route::get('usuarios/email/{email}', [UsuarioController::class, 'getByEmail']);
        
        // Rutas para perfiles (requieren autenticación)
        Route::apiResource('perfiles', PerfilController::class);
        Route::get('perfiles/usuario/{usuario}', [PerfilController::class, 'getByUsuario']);
        
        // Rutas para carrito (requieren autenticación)
        Route::apiResource('carrito', CarritoController::class);
        Route::get('carrito/usuario/{usuario}', [CarritoController::class, 'getByUser']);
        Route::delete('carrito/usuario/{usuario}/clear', [CarritoController::class, 'clearCart']);
        
        // Rutas para órdenes (requieren autenticación)
        Route::apiResource('ordenes', OrdenController::class);
        Route::get('ordenes/usuario/{usuario}', [OrdenController::class, 'getByUser']);
        Route::get('ordenes/tienda/{tienda}', [OrdenController::class, 'getByTienda']);
        Route::patch('ordenes/{orden}/status', [OrdenController::class, 'updateStatus']);

        // Métodos de pago del usuario (CRUD autenticado)
        Route::apiResource('metodos-pago', MetodoPagoController::class);
        Route::get('metodos-pago/usuario/{usuario}', [MetodoPagoController::class, 'getByUser']);
        Route::patch('metodos-pago/{metodo}/predeterminar', [MetodoPagoController::class, 'setDefault']);

        // Tracking de órdenes (SSE y snapshot)
        Route::get('ordenes/{orden}/tracking', [OrdenTrackingController::class, 'get']);
        Route::post('ordenes/{orden}/tracking', [OrdenTrackingController::class, 'upsert']);
        Route::get('ordenes/{orden}/tracking/stream', [OrdenTrackingController::class, 'stream']);
        
        // Rutas para checkout (requieren autenticación)
        Route::post('checkout/process', [CheckoutController::class, 'processCheckout']);
        Route::post('checkout/calculate', [CheckoutController::class, 'calculateTotals']);
        
        // Rutas para productos que requieren autenticación
        Route::post('productos', [ProductoController::class, 'store']);
        Route::put('productos/{producto}', [ProductoController::class, 'update']);
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy']);
        // Eliminar una imagen específica del producto (galería o principal)
        Route::delete('productos/{producto}/imagen', [ProductoController::class, 'deleteImage']);
        Route::patch('productos/{producto}/toggle-status', [ProductoController::class, 'toggleStatus']);
        
        // Rutas para tiendas que requieren autenticación
        Route::post('tiendas', [TiendaController::class, 'store']);
        Route::put('tiendas/{tienda}', [TiendaController::class, 'update']);
        Route::delete('tiendas/{tienda}', [TiendaController::class, 'destroy']);
        Route::patch('tiendas/{tienda}/deactivate', [TiendaController::class, 'deactivate']);

        Route::get('tiendas/{tienda}/horarios', [HorarioTiendaController::class, 'index']);
        Route::put('tiendas/{tienda}/horarios', [HorarioTiendaController::class, 'bulkUpdate']);
        Route::get('tiendas/{tienda}/zonas-delivery', [DireccionEnvioTiendaController::class, 'index']);
        Route::put('tiendas/{tienda}/zonas-delivery', [DireccionEnvioTiendaController::class, 'bulkReplace']);
        Route::get('tiendas/{tienda}/metodos-pago', [TiendaMetodoPagoController::class, 'index']);
        Route::put('tiendas/{tienda}/metodos-pago', [TiendaMetodoPagoController::class, 'setAccepted']);
        
        // Ruta para configurar perfil (requiere autenticación)
        Route::post('perfil/setup', [PerfilController::class, 'createOrUpdateProfile']);
        Route::post('perfil/complete-onboarding', [PerfilController::class, 'completeOnboarding']);
        
        // Rutas para comisiones (requieren autenticación)
        Route::prefix('comisiones')->group(function () {
            // Obtener comisiones de una tienda (propietarios pueden ver las suyas)
            Route::get('tienda/{tiendaId}', [ComisionController::class, 'getComisionesTienda'])
                ->middleware('validar.comisiones:ver');
            
            // Obtener resumen de comisiones de una tienda (propietarios pueden ver las suyas)
            Route::get('tienda/{tiendaId}/resumen', [ComisionController::class, 'getResumenComisionesTienda'])
                ->middleware('validar.comisiones:ver');
            
            // Recalcular comisiones de una orden (solo administradores)
            Route::post('orden/{ordenId}/recalcular', [ComisionController::class, 'recalcularComisionesOrden'])
                ->middleware('validar.comisiones:administrar');
            
            // Obtener estadísticas generales (solo administradores)
            Route::get('estadisticas', [ComisionController::class, 'getEstadisticasGenerales'])
                ->middleware('validar.comisiones:administrar');
        });

        // Clientes por tienda (protegido y autorizado: propietario o admin)
        Route::get('clientes/tienda/{tiendaId}', [ClienteController::class, 'getByTienda'])
            ->middleware('validar.comisiones:ver');
        
        // Rutas para liquidaciones (requieren autenticación)
        Route::prefix('liquidaciones')->group(function () {
            // Crear liquidación (solo administradores)
            Route::post('/', [ComisionController::class, 'crearLiquidacion'])
                ->middleware('validar.comisiones:liquidar');
            
            // Obtener liquidaciones de una tienda (propietarios pueden ver las suyas)
            Route::get('tienda/{tiendaId}', [ComisionController::class, 'getLiquidacionesTienda'])
                ->middleware('validar.comisiones:ver');
            
            // Procesar liquidación (solo administradores)
            Route::patch('{liquidacionId}/procesar', [ComisionController::class, 'procesarLiquidacion'])
                ->middleware('validar.comisiones:liquidar');
            
            // Marcar liquidación como pagada (solo administradores)
            Route::patch('{liquidacionId}/pagar', [ComisionController::class, 'marcarLiquidacionPagada'])
                ->middleware('validar.comisiones:liquidar');
        });
        
        // Rutas para dashboard de administradores (solo administradores)
        Route::prefix('admin/dashboard')->middleware('validar.comisiones:administrar')->group(function () {
            // Estadísticas generales del dashboard
            Route::get('estadisticas', [DashboardAdminController::class, 'getEstadisticasGenerales']);
            
            // Ganancias por período
            Route::get('ganancias', [DashboardAdminController::class, 'getGananciasPorPeriodo']);
            
            // Liquidaciones pendientes
            Route::get('liquidaciones-pendientes', [DashboardAdminController::class, 'getLiquidacionesPendientes']);
            
            // Métricas de rendimiento
            Route::get('metricas', [DashboardAdminController::class, 'getMetricasRendimiento']);
        });

        // Analíticas por tienda (propietario o admin)
        Route::get('analiticas/tienda/{tiendaId}', [DashboardTiendaController::class, 'getAnaliticasTienda'])
            ->middleware('validar.comisiones:ver');
    });
});

// Rutas de autenticación (públicas)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas de autenticación (protegidas)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
});

// Ruta de prueba
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');