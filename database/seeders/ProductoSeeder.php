<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Tienda;
use App\Models\User;
use Carbon\Carbon;


class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productos = [
            [
                'nombre' => 'Smartphone Samsung Galaxy A54',
                'descripcion' => 'Teléfono inteligente con cámara de 50MP, pantalla AMOLED de 6.4 pulgadas y batería de larga duración.',
                'precio' => 2199000,
                'precio_oferta' => 1869150, // 15% descuento
                'stock' => 25,
                'sku' => 'SAM-A54-001',
                'peso' => 0.202,
                'dimensiones' => '158.2 x 76.7 x 8.2 mm',
                'imagen_principal' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1580910051074-3eb694886505?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1556656793-08538906a9f8?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => true,
                'calificacion_promedio' => 4.5,
                'total_ventas' => 128,
                'categoria' => 'Electrónicos',
                'tienda' => 'TechWorld',
            ],
            [
                'nombre' => 'Laptop HP Pavilion 15',
                'descripcion' => 'Laptop con procesador Intel Core i5, 8GB RAM, 256GB SSD, perfecta para trabajo y estudio.',
                'precio' => 4799000,
                'precio_oferta' => 4319100, // 10% descuento
                'stock' => 12,
                'sku' => 'HP-PAV15-001',
                'peso' => 1.75,
                'dimensiones' => '358.5 x 242 x 19.9 mm',
                'imagen_principal' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1484788984921-03950022c9ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => false,
                'calificacion_promedio' => 4.3,
                'total_ventas' => 89,
                'categoria' => 'Electrónicos',
                'tienda' => 'TechWorld',
            ],
            [
                'nombre' => 'Auriculares Sony WH-1000XM4',
                'descripcion' => 'Auriculares inalámbricos con cancelación de ruido activa y hasta 30 horas de batería.',
                'precio' => 1499000,
                'precio_oferta' => 1199200, // 20% descuento
                'stock' => 0,
                'sku' => 'SONY-WH1000XM4-001',
                'peso' => 0.254,
                'dimensiones' => '254 x 203 x 76 mm',
                'imagen_principal' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1583394838336-acd977736f90?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1484704849700-f032a568e944?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => true,
                'calificacion_promedio' => 4.8,
                'total_ventas' => 256,
                'categoria' => 'Electrónicos',
                'tienda' => 'Moda Urbana',
            ],
            [
                'nombre' => 'Camiseta Polo Ralph Lauren',
                'descripcion' => 'Camiseta polo clásica de algodón 100%, disponible en varios colores y tallas.',
                'precio' => 650000,
                'precio_oferta' => null,
                'stock' => 45,
                'sku' => 'RL-POLO-001',
                'peso' => 0.2,
                'dimensiones' => 'Talla M: 71cm largo x 51cm ancho',
                'imagen_principal' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1503341504253-dff4815485f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => false,
                'calificacion_promedio' => 4.2,
                'total_ventas' => 67,
                'categoria' => 'Ropa',
                'tienda' => 'Casa & Hogar',
            ],
            [
                'nombre' => 'Zapatillas Nike Air Max 270',
                'descripcion' => 'Zapatillas deportivas con tecnología Air Max para máxima comodidad y estilo urbano.',
                'precio' => 950000,
                'precio_oferta' => 712500, // 25% descuento
                'stock' => 8,
                'sku' => 'NIKE-AM270-001',
                'peso' => 0.35,
                'dimensiones' => 'Talla 42: 29cm largo',
                'imagen_principal' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => true,
                'calificacion_promedio' => 4.6,
                'total_ventas' => 194,
                'categoria' => 'Calzado',
                'tienda' => 'TechWorld',
            ],
            [
                'nombre' => 'Cafetera Nespresso Vertuo',
                'descripcion' => 'Cafetera de cápsulas con tecnología Centrifusion para el café perfecto.',
                'precio' => 1299000,
                'precio_oferta' => 1104150, // 15% descuento
                'stock' => 0,
                'sku' => 'NESP-VERTUO-001',
                'peso' => 4.2,
                'dimensiones' => '370 x 314 x 270 mm',
                'imagen_principal' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1509042239860-f550ce710b93?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => false,
                'calificacion_promedio' => 4.4,
                'total_ventas' => 112,
                'categoria' => 'Hogar',
                'tienda' => 'Moda Urbana',
            ],
            [
                'nombre' => 'Tablet iPad Air 5ta Gen',
                'descripcion' => 'Tablet con chip M1, pantalla Liquid Retina de 10.9 pulgadas y compatibilidad con Apple Pencil.',
                'precio' => 4399000,
                'precio_oferta' => 4047080, // 8% descuento
                'stock' => 18,
                'sku' => 'APPLE-IPADAIR5-001',
                'peso' => 0.461,
                'dimensiones' => '247.6 x 178.5 x 6.1 mm',
                'imagen_principal' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1561154464-82e9adf32764?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1587033411391-5d9e51cce126?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1512499617640-c74ae3a79d37?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => true,
                'calificacion_promedio' => 4.7,
                'total_ventas' => 203,
                'categoria' => 'Electrónicos',
                'tienda' => 'TechWorld',
            ],
            [
                'nombre' => 'Perfume Chanel No. 5',
                'descripcion' => 'Fragancia icónica y atemporal, eau de parfum de 100ml.',
                'precio' => 1099000,
                'precio_oferta' => null,
                'stock' => 0,
                'sku' => 'CHANEL-NO5-001',
                'peso' => 0.15,
                'dimensiones' => '100ml - 10.5 x 5.5 x 5.5 cm',
                'imagen_principal' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                'imagenes_adicionales' => [
                    'https://images.unsplash.com/photo-1594035910387-fea47794261f?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                    'https://images.unsplash.com/photo-1615634260167-c8cdede054de?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'
                ],
                'activo' => true,
                'destacado' => false,
                'calificacion_promedio' => 4.9,
                'total_ventas' => 89,
                'categoria' => 'Belleza',
                'tienda' => 'Casa & Hogar',
            ],
        ];

        foreach ($productos as $productoData) {
            // Obtener IDs de categoría y tienda
            $categoria = Categoria::where('nombre', $productoData['categoria'])->first();
            $tienda = Tienda::where('nombre_tienda', $productoData['tienda'])->first();
            
            if ($categoria && $tienda) {
                Producto::create([
                    'id_tienda' => $tienda->id_tienda,
                    'user_id' => $tienda->user_id,
                    'id_categoria' => $categoria->id_categoria,
                    'nombre' => $productoData['nombre'],
                    'descripcion' => $productoData['descripcion'],
                    'precio' => $productoData['precio'],
                    'cantidad_stock' => $productoData['stock'],
                    'peso' => $productoData['peso'],
                    'dimensiones' => $productoData['dimensiones'],
                    'marca' => 'Genérica',
                    'modelo' => 'Estándar',
                    'condicion' => 'nuevo',
                    'tipo_vendedor' => 'directa',
                    'estado' => 'activo',
                    'activo' => $productoData['activo'],
                    'destacado' => $productoData['destacado'],
                ]);
            }
        }
    }
}