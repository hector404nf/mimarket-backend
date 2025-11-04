<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categoria>
 */
class CategoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categorias = [
            'Electrónicos' => 'Productos electrónicos y tecnología',
            'Ropa y Moda' => 'Vestimenta y accesorios de moda',
            'Hogar y Jardín' => 'Artículos para el hogar y jardinería',
            'Deportes' => 'Equipos y accesorios deportivos',
            'Libros' => 'Libros y material de lectura',
            'Salud y Belleza' => 'Productos de cuidado personal',
            'Juguetes' => 'Juguetes y entretenimiento infantil',
            'Automóviles' => 'Accesorios y repuestos para vehículos',
            'Mascotas' => 'Productos para el cuidado de mascotas',
            'Alimentación' => 'Productos alimenticios y bebidas',
        ];

        $categoria = fake()->randomElement(array_keys($categorias));
        
        return [
            'nombre' => $categoria,
            'slug' => fake()->unique()->slug(),
            'description' => $categorias[$categoria],
            'icono' => fake()->optional()->word(),
            'activo' => true,
            'orden' => fake()->numberBetween(1, 100),
        ];
    }
}