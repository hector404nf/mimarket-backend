<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->words(3, true);
        
        return [
            'id_categoria' => Categoria::factory(),
            'id_tienda' => Tienda::factory(),
            'user_id' => User::factory(),
            'nombre' => ucfirst($nombre),
            'descripcion' => fake()->paragraph(3),
            'precio' => fake()->randomFloat(2, 10, 1000),
            'cantidad_stock' => fake()->numberBetween(0, 100),
            'estado' => fake()->randomElement(['activo', 'inactivo', 'agotado']),
            'destacado' => fake()->boolean(20), // 20% chance of being featured
            'peso' => fake()->randomFloat(3, 0.1, 10),
            'dimensiones' => fake()->optional()->regexify('[0-9]{1,2}x[0-9]{1,2}x[0-9]{1,2}'),
            'marca' => fake()->optional()->company(),
            'modelo' => fake()->optional()->bothify('Model-###'),
            'condicion' => fake()->randomElement(['nuevo', 'usado', 'reacondicionado']),
            'tipo_vendedor' => fake()->randomElement(['directa', 'pedido', 'delivery']),
            'activo' => true,
        ];
    }

    /**
     * Create a product for a specific category
     */
    public function forCategoria($categoriaId): static
    {
        return $this->state(fn (array $attributes) => [
            'id_categoria' => $categoriaId,
        ]);
    }

    /**
     * Create a product for a specific store
     */
    public function forTienda($tiendaId): static
    {
        return $this->state(fn (array $attributes) => [
            'id_tienda' => $tiendaId,
        ]);
    }

    /**
     * Create a product for a specific user
     */
    public function forUser($userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    /**
     * Indicate that the product should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Indicate that the product should be featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'destacado' => true,
        ]);
    }

    /**
     * Indicate that the product should be out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'cantidad_stock' => 0,
            'estado' => 'agotado',
        ]);
    }
}