<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tienda>
 */
class TiendaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombreTienda = $this->faker->company;
        return [
            'user_id' => User::factory(),
            'nombre_tienda' => $nombreTienda,
            'slug' => \Str::slug($nombreTienda) . '-' . $this->faker->randomNumber(4),
            'descripcion' => $this->faker->paragraph,
            'direccion' => $this->faker->address,
            'telefono_contacto' => $this->faker->phoneNumber,
            'email_contacto' => $this->faker->companyEmail,
            'logo' => $this->faker->imageUrl(200, 200, 'business'),
            'banner' => $this->faker->imageUrl(800, 300, 'business'),
            'calificacion_promedio' => $this->faker->randomFloat(2, 1, 5),
            'total_productos' => $this->faker->numberBetween(0, 100),
            'verificada' => $this->faker->boolean(80), // 80% chance of being verified
        ];
    }

    /**
     * Indicate that the store should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verificada' => false,
        ]);
    }
}