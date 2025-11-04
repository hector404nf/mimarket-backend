<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar seeders en orden correcto
        $this->call([
            CategoriaSeeder::class,
            TiendaSeeder::class,
            ProductoSeeder::class,
            FixProductStockSeeder::class,
        ]);
    }
}
