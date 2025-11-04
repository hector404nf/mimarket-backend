<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;

class FixProductStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Asegurar que ningún producto tenga cantidad_stock en NULL
        $productosNull = Producto::whereNull('cantidad_stock')->count();
        if ($productosNull > 0) {
            Producto::whereNull('cantidad_stock')->update(['cantidad_stock' => 50]);
            $this->command?->info("Productos con cantidad_stock NULL actualizados a 50: {$productosNull}");
        }

        // Corregir stock específico para Smartphone Samsung Galaxy A54
        $nombreProducto = 'Smartphone Samsung Galaxy A54';
        $producto = Producto::where('nombre', $nombreProducto)->first();

        if ($producto) {
            $nuevoStock = 50; // Valor deseado para garantizar disponibilidad
            $producto->cantidad_stock = $nuevoStock;
            $producto->save();
            $this->command?->info("Stock actualizado del producto '{$nombreProducto}' a {$nuevoStock}.");
        } else {
            $this->command?->warn("Producto '{$nombreProducto}' no encontrado. No se realizaron cambios.");
        }
    }
}