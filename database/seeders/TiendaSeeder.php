<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tienda;
use App\Models\User;
use Carbon\Carbon;

class TiendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios para las tiendas si no existen
        $usuarios = [
            [
                'name' => 'TechWorld',
                'apellido' => 'Admin',
                'email' => 'admin@techworld.com',
                'password' => bcrypt('password123'),
                'telefono' => '+595 21 345 678',
                'activo' => true,
            ],
            [
                'name' => 'Moda Urbana',
                'apellido' => 'Admin',
                'email' => 'admin@modaurbana.com',
                'password' => bcrypt('password123'),
                'telefono' => '+595 61 567 890',
                'activo' => true,
            ],
            [
                'name' => 'Casa Hogar',
                'apellido' => 'Admin',
                'email' => 'admin@casayhogar.com',
                'password' => bcrypt('password123'),
                'telefono' => '+595 21 789 012',
                'activo' => true,
            ],
        ];

        foreach ($usuarios as $userData) {
            User::firstOrCreate(['email' => $userData['email']], $userData);
        }

        $tiendas = [
            [
                'user_id' => User::where('email', 'admin@techworld.com')->first()->id,
                'nombre_tienda' => 'TechWorld',
                'slug' => 'techworld',
                'descripcion' => 'Tu tienda de tecnología de confianza. Especialistas en smartphones, laptops y accesorios tecnológicos de última generación.',
                'categoria_principal' => 'Tecnología',
                'logo' => 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=100&h=100&fit=crop',
                'banner' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800&h=300&fit=crop',
                'direccion' => 'Av. Mariscal López 123, Asunción, Paraguay',
                'telefono_contacto' => '+595 21 345 678',
                'email_contacto' => 'contacto@techworld.com',
                'calificacion_promedio' => 4.8,
                'total_productos' => 0,
                'verificada' => true,
            ],
            [
                'user_id' => User::where('email', 'admin@modaurbana.com')->first()->id,
                'nombre_tienda' => 'Moda Urbana',
                'slug' => 'moda-urbana',
                'descripcion' => 'Las últimas tendencias en moda urbana y streetwear. Ropa de calidad para hombres y mujeres que buscan estilo único.',
                'categoria_principal' => 'Moda',
                'logo' => 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=100&h=100&fit=crop',
                'banner' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800&h=300&fit=crop',
                'direccion' => 'Av. San Martín 456, Ciudad del Este, Paraguay',
                'telefono_contacto' => '+595 61 567 890',
                'email_contacto' => 'info@modaurbana.com',
                'calificacion_promedio' => 4.6,
                'total_productos' => 0,
                'verificada' => true,
            ],
            [
                'user_id' => User::where('email', 'admin@casayhogar.com')->first()->id,
                'nombre_tienda' => 'Casa & Hogar',
                'slug' => 'casa-hogar',
                'descripcion' => 'Todo lo que necesitas para hacer de tu casa un hogar. Decoración, muebles y electrodomésticos de calidad.',
                'categoria_principal' => 'Hogar',
                'logo' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=100&h=100&fit=crop',
                'banner' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800&h=300&fit=crop',
                'direccion' => 'Av. Eusebio Ayala 789, Luque, Paraguay',
                'telefono_contacto' => '+595 21 789 012',
                'email_contacto' => 'ventas@casayhogar.com',
                'calificacion_promedio' => 4.7,
                'total_productos' => 0,
                'verificada' => true,
            ],
        ];

        foreach ($tiendas as $tienda) {
            Tienda::create($tienda);
        }
    }
}