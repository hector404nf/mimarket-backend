<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Tienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->categoria = Categoria::factory()->create();
        $this->tienda = Tienda::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Test can get list of active products.
     */
    public function test_can_get_list_of_active_products(): void
    {
        // Create active products
        Producto::factory()->count(3)->create([
            'activo' => true,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        // Create inactive product (should not appear)
        Producto::factory()->create([
            'activo' => false,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/productos');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id_producto',
                            'nombre',
                            'descripcion',
                            'precio',
                            'stock',
                            'activo',
                            'categoria',
                            'tienda',
                            'usuario',
                        ]
                    ]
                ]);

        // Should only return active products
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test can filter products by category.
     */
    public function test_can_filter_products_by_category(): void
    {
        $categoria2 = Categoria::factory()->create();

        // Create products in different categories
        Producto::factory()->count(2)->create([
            'activo' => true,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        Producto::factory()->create([
            'activo' => true,
            'id_categoria' => $categoria2->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/productos?categoria=' . $this->categoria->id_categoria);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test can filter products by price range.
     */
    public function test_can_filter_products_by_price_range(): void
    {
        // Create products with different prices
        Producto::factory()->create([
            'activo' => true,
            'precio' => 10.00,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        Producto::factory()->create([
            'activo' => true,
            'precio' => 25.00,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        Producto::factory()->create([
            'activo' => true,
            'precio' => 50.00,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/productos?precio_min=15&precio_max=30');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(25.00, $response->json('data.0.precio'));
    }

    /**
     * Test can search products by name.
     */
    public function test_can_search_products_by_name(): void
    {
        Producto::factory()->create([
            'activo' => true,
            'nombre' => 'iPhone 15 Pro',
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        Producto::factory()->create([
            'activo' => true,
            'nombre' => 'Samsung Galaxy',
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/productos?buscar=iPhone');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('iPhone', $response->json('data.0.nombre'));
    }

    /**
     * Test can get featured products.
     */
    public function test_can_get_featured_products(): void
    {
        // Create featured and non-featured products
        Producto::factory()->count(2)->create([
            'activo' => true,
            'destacado' => true,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        Producto::factory()->create([
            'activo' => true,
            'destacado' => false,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/productos?destacado=1');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
        
        foreach ($response->json('data') as $producto) {
            $this->assertTrue($producto['destacado']);
        }
    }

    /**
     * Test can get single product details.
     */
    public function test_can_get_single_product_details(): void
    {
        $producto = Producto::factory()->create([
            'activo' => true,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/productos/' . $producto->id_producto);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id_producto',
                        'nombre',
                        'descripcion',
                        'precio',
                        'stock',
                        'categoria',
                        'tienda',
                        'usuario',
                    ]
                ]);

        $this->assertEquals($producto->id_producto, $response->json('data.id_producto'));
    }

    /**
     * Test returns 404 for non-existent product.
     */
    public function test_returns_404_for_non_existent_product(): void
    {
        $response = $this->getJson('/api/v1/productos/99999');

        $response->assertStatus(404);
    }

    /**
     * Test authenticated user can create product.
     */
    public function test_authenticated_user_can_create_product(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $productData = [
            'nombre' => 'Test Product',
            'descripcion' => 'Test Description',
            'precio' => 29.99,
            'cantidad_stock' => 10,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'tipo_vendedor' => 'directa',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/productos', $productData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id_producto',
                        'nombre',
                        'descripcion',
                        'precio',
                        'stock',
                        'id_categoria',
                        'id_tienda',
                        'user_id',
                    ]
                ]);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Test Product',
            'precio' => 29.99,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test unauthenticated user cannot create product.
     */
    public function test_unauthenticated_user_cannot_create_product(): void
    {
        $productData = [
            'nombre' => 'Test Product',
            'descripcion' => 'Test Description',
            'precio' => 29.99,
            'cantidad_stock' => 10,
            'id_categoria' => $this->categoria->id_categoria,
            'id_tienda' => $this->tienda->id_tienda,
            'tipo_vendedor' => 'directa',
        ];

        $response = $this->postJson('/api/v1/productos', $productData);

        $response->assertStatus(401);
    }

    /**
     * Test product creation validation.
     */
    public function test_product_creation_validation(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $invalidData = [
            'nombre' => '', // Required
            'precio' => -10, // Must be positive
            'cantidad_stock' => -5, // Must be positive
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/productos', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['nombre', 'precio', 'cantidad_stock']);
    }
}