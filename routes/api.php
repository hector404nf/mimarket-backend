<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\TiendaController;
use App\Http\Controllers\Api\UsuarioController;

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
    
    // Categorías
    Route::apiResource('categorias', CategoriaController::class);
    
    // Productos
    Route::apiResource('productos', ProductoController::class);
    
    // Tiendas
    Route::apiResource('tiendas', TiendaController::class);
    
    // Usuarios
    Route::apiResource('usuarios', UsuarioController::class);
    
    // Rutas adicionales específicas
    Route::get('categorias/{id}/productos', function($id) {
        return app(ProductoController::class)->index(request()->merge(['categoria' => $id]));
    });
    
    Route::get('tiendas/{id}/productos', function($id) {
        return app(ProductoController::class)->index(request()->merge(['tienda' => $id]));
    });
    
    Route::get('usuarios/{id}/tiendas', function($id) {
        return app(TiendaController::class)->index(request()->merge(['usuario' => $id]));
    });
    
    Route::get('usuarios/{id}/productos', function($id) {
        return app(ProductoController::class)->index(request()->merge(['usuario' => $id]));
    });
});

// Ruta de prueba
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');