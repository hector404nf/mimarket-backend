<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /**
     * Test can get list of all categories.
     */
    public function test_can_get_list_of_categories(): void
    {
        Categoria::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/categorias');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'nombre',
                            'descripcion',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test can get single category details.
     */
    public function test_can_get_single_category_details(): void
    {
        $categoria = Categoria::factory()->create([
            'nombre' => 'Electrónicos',
            'description' => 'Productos electrónicos y tecnología',
        ]);

        $response = $this->getJson('/api/v1/categorias/' . $categoria->id_categoria);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'nombre',
                        'descripcion',
                        'created_at',
                        'updated_at',
                    ]
                ]);

        $this->assertEquals('Electrónicos', $response->json('data.nombre'));
        $this->assertEquals($categoria->id_categoria, $response->json('data.id'));
    }

    /**
     * Test returns 404 for non-existent category.
     */
    public function test_returns_404_for_non_existent_category(): void
    {
        $response = $this->getJson('/api/v1/categorias/99999');

        $response->assertStatus(404);
    }

    /**
     * Test authenticated user can create category.
     */
    public function test_authenticated_user_can_create_category(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $categoryData = [
            'nombre' => 'Nueva Categoría',
            'descripcion' => 'Descripción de la nueva categoría',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/categorias', $categoryData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'nombre',
                        'descripcion',
                    ]
                ]);

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Nueva Categoría',
            'description' => 'Descripción de la nueva categoría',
        ]);
    }

    /**
     * Test unauthenticated user cannot create category.
     */
    public function test_unauthenticated_user_cannot_create_category(): void
    {
        $categoryData = [
            'nombre' => 'Nueva Categoría',
            'descripcion' => 'Descripción de la nueva categoría',
        ];

        $response = $this->postJson('/api/v1/categorias', $categoryData);

        $response->assertStatus(401);
    }

    /**
     * Test category creation validation.
     */
    public function test_category_creation_validation(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $invalidData = [
            'nombre' => '', // Required field
            'descripcion' => str_repeat('a', 1001), // Too long
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/categorias', $invalidData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['nombre']);
    }

    /**
     * Test authenticated user can update category.
     */
    public function test_authenticated_user_can_update_category(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;
        $categoria = Categoria::factory()->create();

        $updateData = [
            'nombre' => 'Categoría Actualizada',
            'descripcion' => 'Descripción actualizada',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/categorias/' . $categoria->id_categoria, $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'nombre',
                        'descripcion',
                    ]
                ]);

        $this->assertDatabaseHas('categorias', [
            'id_categoria' => $categoria->id_categoria,
            'nombre' => 'Categoría Actualizada',
            'description' => 'Descripción actualizada',
        ]);
    }

    /**
     * Test authenticated user can delete category.
     */
    public function test_authenticated_user_can_delete_category(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;
        $categoria = Categoria::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/categorias/' . $categoria->id_categoria);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Categoría eliminada exitosamente',
                ]);

        $this->assertDatabaseMissing('categorias', [
            'id_categoria' => $categoria->id_categoria,
        ]);
    }

    /**
     * Test cannot delete category with associated products.
     */
    public function test_cannot_delete_category_with_products(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;
        $categoria = Categoria::factory()->create();
        
        // Create a product associated with this category
        Producto::factory()->create([
            'id_categoria' => $categoria->id_categoria,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/categorias/' . $categoria->id_categoria);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene productos asociados',
                ]);

        $this->assertDatabaseHas('categorias', [
            'id_categoria' => $categoria->id_categoria,
        ]);
    }

    /**
     * Test can get categories with product count.
     */
    public function test_can_get_categories_with_product_count(): void
    {
        // Limpiar datos existentes para asegurar un test aislado
        Producto::query()->delete();
        Categoria::query()->delete();
        
        $categoria1 = Categoria::factory()->create();
        $categoria2 = Categoria::factory()->create();

        // Create products for categoria1
        Producto::factory()->count(3)->create([
            'id_categoria' => $categoria1->id_categoria,
            'activo' => true,
        ]);

        // Create products for categoria2
        Producto::factory()->count(2)->create([
            'id_categoria' => $categoria2->id_categoria,
            'activo' => true,
        ]);

        // Create inactive product (should not be counted)
        Producto::factory()->create([
            'id_categoria' => $categoria1->id_categoria,
            'activo' => false,
        ]);

        $response = $this->getJson('/api/v1/categorias?include_product_count=true');

        $response->assertStatus(200);
        
        $categorias = $response->json('data');
        
        foreach ($categorias as $categoria) {
            if ($categoria['id_categoria'] == $categoria1->id_categoria) {
                $this->assertEquals(3, $categoria['productos_count']);
            } elseif ($categoria['id_categoria'] == $categoria2->id_categoria) {
                $this->assertEquals(2, $categoria['productos_count']);
            }
        }
    }
}