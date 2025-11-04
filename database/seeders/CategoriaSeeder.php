<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;
use Carbon\Carbon;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Electrónicos',
                'description' => 'Dispositivos electrónicos, smartphones, laptops, tablets y accesorios tecnológicos',
                'icono_url' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 1,
            ],
            [
                'nombre' => 'Ropa',
                'description' => 'Vestimenta para hombres, mujeres y niños. Moda urbana y clásica',
                'icono_url' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 2,
            ],
            [
                'nombre' => 'Calzado',
                'description' => 'Zapatos, zapatillas deportivas, botas y calzado para toda ocasión',
                'icono_url' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 3,
            ],
            [
                'nombre' => 'Hogar',
                'description' => 'Electrodomésticos, decoración, muebles y artículos para el hogar',
                'icono_url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 4,
            ],
            [
                'nombre' => 'Belleza',
                'description' => 'Productos de belleza, cuidado personal, perfumes y cosméticos',
                'icono_url' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 5,
            ],
            [
                'nombre' => 'Deportes',
                'description' => 'Equipamiento deportivo, ropa deportiva y accesorios para fitness',
                'icono_url' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 6,
            ],
            [
                'nombre' => 'Libros',
                'description' => 'Libros físicos y digitales, literatura, educación y entretenimiento',
                'icono_url' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 7,
            ],
            [
                'nombre' => 'Juguetes',
                'description' => 'Juguetes para niños, juegos educativos y entretenimiento infantil',
                'icono_url' => 'https://images.unsplash.com/photo-1558060370-d644479cb6f7?w=300&h=300&fit=crop&crop=center',
                'activo' => true,
                'orden' => 8,
            ],
        ];

        foreach ($categorias as $categoriaData) {
            // Extraer la URL del icono
            $iconoUrl = $categoriaData['icono_url'];
            unset($categoriaData['icono_url']);
            
            // Crear la categoría
            $categoria = Categoria::create($categoriaData);
            
            // Agregar la imagen usando Spatie Media Library
            try {
                $categoria->addMediaFromUrl($iconoUrl)
                    ->toMediaCollection('icono');
            } catch (\Exception $e) {
                // Si falla la descarga de la imagen, continuar sin imagen
                \Log::warning("No se pudo descargar la imagen para la categoría {$categoria->nombre}: " . $e->getMessage());
            }
        }
    }
}