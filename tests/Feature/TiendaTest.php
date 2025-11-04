<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tienda;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TiendaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->vendedor = User::factory()->create();
    }

    /**
     * Test can get list of verified stores.
     */
    public function test_can_get_list_of_verified_stores(): void
    {
        Tienda::factory()->count(3)->create(['verificada' => true]);
        Tienda::factory()->count(2)->create(['verificada' => false]);

        $response = $this->getJson('/api/v1/tiendas');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id_tienda',
                            'nombre_tienda',
                            'descripcion',
                            'direccion',
                            'telefono_contacto',
                            'email_contacto',
                            'verificada',
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total'
                ]);

        $this->assertCount(3, $response->json('data'));
        
        foreach ($response->json('data') as $tienda) {
            $this->assertTrue($tienda['verificada']);
        }
    }

    /**
     * Test can get single store details.
     */
    public function test_can_get_single_store_details(): void
    {
        $tienda = Tienda::factory()->create([
            'nombre_tienda' => 'Mi Tienda Test',
            'descripcion' => 'Descripción de prueba',
            'verificada' => true,
        ]);

        $response = $this->getJson('/api/v1/tiendas/' . $tienda->id_tienda);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id_tienda',
                        'nombre_tienda',
                        'descripcion',
                        'direccion',
                        'telefono_contacto',
                        'email_contacto',
                        'verificada',
                    ]
                ]);

        $this->assertEquals('Mi Tienda Test', $response->json('data.nombre_tienda'));
        $this->assertEquals($tienda->id_tienda, $response->json('data.id_tienda'));
    }

    /**
     * Test returns 404 for non-existent store.
     */
    public function test_returns_404_for_non_existent_store(): void
    {
        $response = $this->getJson('/api/v1/tiendas/99999');

        $response->assertStatus(404);
    }

    /**
     * Test returns 404 for inactive store.
     */
    public function test_returns_404_for_inactive_store(): void
    {
        $tienda = Tienda::factory()->create(['verificada' => false]);

        $response = $this->getJson('/api/v1/tiendas/' . $tienda->id_tienda);

        $response->assertStatus(404);
    }

    /**
     * Test authenticated vendor can create store.
     */
    public function test_authenticated_vendor_can_create_store(): void
    {
        $token = $this->vendedor->createToken('test-token')->plainTextToken;

        $storeData = [
            'nombre_tienda' => 'Nueva Tienda',
            'descripcion' => 'Descripción de la nueva tienda',
            'direccion' => 'Calle 123, Ciudad',
            'telefono_contacto' => '+1234567890',
            'email_contacto' => 'tienda@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/tiendas', $storeData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id_tienda',
                        'nombre_tienda',
                        'descripcion',
                        'direccion',
                        'telefono_contacto',
                        'email_contacto',
                        'verificada',
                    ]
                ]);

        $this->assertDatabaseHas('tiendas', [
            'nombre_tienda' => 'Nueva Tienda',
            'user_id' => $this->vendedor->id,
        ]);
    }

    /**
     * Test regular user cannot create store.
     */
    public function test_regular_user_cannot_create_store(): void
    {
        // Crear una tienda para este usuario primero
        Tienda::factory()->create(['user_id' => $this->user->id]);
        
        $token = $this->user->createToken('test-token')->plainTextToken;

        $storeData = [
            'nombre_tienda' => 'Nueva Tienda',
            'descripcion' => 'Descripción de la nueva tienda',
            'direccion' => 'Calle 123, Ciudad',
            'telefono_contacto' => '+1234567890',
            'email_contacto' => 'tienda@example.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/tiendas', $storeData);

        $response->assertStatus(403);
    }

    /**
     * Test unauthenticated user cannot create store.
     */
    public function test_unauthenticated_user_cannot_create_store(): void
    {
        $storeData = [
            'nombre' => 'Nueva Tienda',
            'descripcion' => 'Descripción de la nueva tienda',
            'direccion' => 'Calle 123, Ciudad',
            'telefono' => '+1234567890',
            'email' => 'tienda@example.com',
        ];

        $response = $this->postJson('/api/v1/tiendas', $storeData);

        $response->assertStatus(401);
    }

    /**
     * Test store creation validation.
     */
    public function test_store_creation_validation(): void
    {
        $token = $this->vendedor->createToken('test-token')->plainTextToken;

        $invalidData = [
            'nombre_tienda' => '', // Required field
            'email_contacto' => 'invalid-email', // Invalid email format
            'telefono_contacto' => '123', // Too short
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/tiendas', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['nombre_tienda', 'email_contacto']);
    }

    /**
     * Test store owner can update their store.
     */
    public function test_store_owner_can_update_their_store(): void
    {
        $tienda = Tienda::factory()->create(['user_id' => $this->vendedor->id]);
        $token = $this->vendedor->createToken('test-token')->plainTextToken;

        $updateData = [
            'nombre_tienda' => 'Tienda Actualizada',
            'descripcion' => 'Descripción actualizada',
            'telefono_contacto' => '+9876543210',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/tiendas/' . $tienda->id_tienda, $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id_tienda',
                        'nombre_tienda',
                        'descripcion',
                        'telefono_contacto',
                    ]
                ]);

        $this->assertDatabaseHas('tiendas', [
            'id_tienda' => $tienda->id_tienda,
            'nombre_tienda' => 'Tienda Actualizada',
            'telefono_contacto' => '+9876543210',
        ]);
    }

    /**
     * Test user cannot update store they don't own.
     */
    public function test_user_cannot_update_store_they_dont_own(): void
    {
        $otherVendedor = User::factory()->create();
        $tienda = Tienda::factory()->create(['user_id' => $otherVendedor->id]);
        $token = $this->vendedor->createToken('test-token')->plainTextToken;

        $updateData = [
            'nombre_tienda' => 'Tienda Actualizada',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/tiendas/' . $tienda->id_tienda, $updateData);

        $response->assertStatus(403);
    }

    /**
     * Test can get store products.
     */
    public function test_can_get_store_products(): void
    {
        $tienda = Tienda::factory()->create(['verificada' => true]);
        
        // Create active products for this store
        Producto::factory()->count(3)->create([
            'id_tienda' => $tienda->id_tienda,
            'estado' => 'activo',
        ]);
        
        // Create inactive product (should not be included)
        Producto::factory()->create([
            'id_tienda' => $tienda->id_tienda,
            'estado' => 'inactivo',
        ]);

        $response = $this->getJson('/api/v1/tiendas/' . $tienda->id_tienda . '/productos');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id_producto',
                            'nombre',
                            'precio',
                            'estado',
                        ]
                    ]
                ]);

        $this->assertCount(3, $response->json('data'));
        
        foreach ($response->json('data') as $producto) {
            $this->assertEquals('activo', $producto['estado']);
        }
    }

    /**
     * Test can search stores by name.
     */
    public function test_can_search_stores_by_name(): void
    {
        Tienda::factory()->create([
            'nombre_tienda' => 'Electrónicos García',
            'verificada' => true,
        ]);
        
        Tienda::factory()->create([
            'nombre_tienda' => 'Ropa y Moda',
            'verificada' => true,
        ]);

        Tienda::factory()->create([
            'nombre_tienda' => 'García Deportes',
            'verificada' => true,
        ]);

        $response = $this->getJson('/api/v1/tiendas?search=García');

        $response->assertStatus(200);
        
        $tiendas = $response->json('data');
        $this->assertCount(2, $tiendas);
        
        foreach ($tiendas as $tienda) {
            $this->assertStringContainsString('García', $tienda['nombre_tienda']);
        }
    }

    /**
     * Test can filter stores by location.
     */
    public function test_can_filter_stores_by_location(): void
    {
        Tienda::factory()->create([
            'direccion' => 'Calle 123, Bogotá',
            'verificada' => true,
        ]);
        
        Tienda::factory()->create([
            'direccion' => 'Avenida 456, Medellín',
            'verificada' => true,
        ]);

        Tienda::factory()->create([
            'direccion' => 'Carrera 789, Bogotá',
            'verificada' => true,
        ]);

        $response = $this->getJson('/api/v1/tiendas?ciudad=Bogotá');

        $response->assertStatus(200);
        
        $tiendas = $response->json('data');
        $this->assertCount(2, $tiendas);
        
        foreach ($tiendas as $tienda) {
            $this->assertStringContainsString('Bogotá', $tienda['direccion']);
        }
    }

    /**
     * Test store owner can deactivate their store.
     */
    public function test_store_owner_can_deactivate_their_store(): void
    {
        $tienda = Tienda::factory()->create([
            'user_id' => $this->vendedor->id,
            'verificada' => true,
        ]);
        
        $token = $this->vendedor->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('/api/v1/tiendas/' . $tienda->id_tienda . '/deactivate');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Tienda desactivada exitosamente',
                ]);

        $this->assertDatabaseHas('tiendas', [
            'id_tienda' => $tienda->id_tienda,
            'verificada' => false,
        ]);
    }
}