<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_decrements_stock_and_clears_cart(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $producto = Producto::factory()->create([
            'cantidad_stock' => 5,
            'precio' => 10000,
            'activo' => true,
            'estado' => 'activo',
        ]);

        Carrito::create([
            'user_id' => $user->id,
            'id_producto' => $producto->id_producto,
            'cantidad' => 3,
            'precio_unitario' => 10000,
        ]);

        $response = $this->postJson('/api/v1/checkout/process', [
            'metodo_pago' => 'efectivo',
            'direccion_envio' => 'Calle Falsa 123',
        ]);

        $response->assertStatus(201);

        $producto->refresh();
        $this->assertEquals(2, $producto->cantidad_stock, 'El stock del producto debe decrementarse correctamente');

        // Carrito debe vaciarse (tabla 'carrito')
        $this->assertDatabaseMissing('carrito', [
            'user_id' => $user->id,
            'id_producto' => $producto->id_producto,
        ]);
    }

    public function test_checkout_error_returns_stock_disponible_field(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $producto = Producto::factory()->create([
            'cantidad_stock' => 2,
            'precio' => 15000,
            'activo' => true,
            'estado' => 'activo',
        ]);

        Carrito::create([
            'user_id' => $user->id,
            'id_producto' => $producto->id_producto,
            'cantidad' => 3,
            'precio_unitario' => 15000,
        ]);

        $response = $this->postJson('/api/v1/checkout/process', [
            'metodo_pago' => 'tarjeta',
            'direccion_envio' => 'Av. Principal 456',
        ]);

        $response->assertStatus(400)
                 ->assertJsonFragment([
                     'stock_disponible' => 2,
                 ]);
    }
}