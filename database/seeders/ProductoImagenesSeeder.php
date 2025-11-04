<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductoImagenesSeeder extends Seeder
{
    /**
     * Mapeo de productos a URLs de imágenes de Unsplash
     * Basado en los nombres de productos existentes en la BD
     */
    private $imagenesProductos = [
        // Smartphone Samsung Galaxy A54
        'smartphone samsung galaxy a54' => [
            'images' => [
                'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1580910051074-3eb694886505?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1556656793-08538906a9f8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ],
        
        // Laptop HP Pavilion 15
        'laptop hp pavilion 15' => [
            'images' => [
                'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1484788984921-03950022c9ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ],
        
        // Auriculares Sony WH-1000XM4
        'auriculares sony wh-1000xm4' => [
            'images' => [
                'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1583394838336-acd977736f90?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1484704849700-f032a568e944?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1545127398-14699f92334b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ],
        
        // Camiseta Polo Ralph Lauren
        'camiseta polo ralph lauren' => [
            'images' => [
                'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1503341504253-dff4815485f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ],
        
        // Zapatillas Nike Air Max 270
        'zapatillas nike air max 270' => [
            'images' => [
                'https://images.unsplash.com/photo-1549298916-b41d501d3772?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1551107696-a4b0c5a0d9a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ],
        
        // Cafetera Nespresso Vertuo
        'cafetera nespresso vertuo' => [
            'images' => [
                'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1447933601403-0c6688de566e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1509042239860-f550ce710b93?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ],
        
        // Tablet iPad Air 5ta Gen
        'tablet ipad air 5ta gen' => [
            'images' => [
                'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1561154464-82e9adf32764?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1585790050230-5dd28404ccb9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1512499617640-c74ae3a79d37?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ],
        
        // Perfume Chanel No. 5
        'perfume chanel no. 5' => [
            'images' => [
                'https://images.unsplash.com/photo-1541643600914-78b084683601?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ],
            'gallery' => [
                'https://images.unsplash.com/photo-1588405748880-12d1d2a59d75?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1594035910387-fea47794261f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1563170351-be82bc888aa4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
            ]
        ]
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🖼️  Iniciando seeder de imágenes para productos...');
        
        // Obtener todos los productos
        $productos = Producto::all();
        
        if ($productos->isEmpty()) {
            $this->command->warn('⚠️  No se encontraron productos en la base de datos.');
            return;
        }

        $this->command->info("📦 Encontrados {$productos->count()} productos para procesar.");

        foreach ($productos as $producto) {
            $this->procesarProducto($producto);
        }

        $this->command->info('✅ Seeder de imágenes completado exitosamente!');
    }

    /**
     * Procesar un producto individual
     */
    private function procesarProducto(Producto $producto): void
    {
        $nombreProducto = strtolower($producto->nombre);
        $this->command->info("🔍 Procesando: {$producto->nombre}");

        // Buscar imágenes para este producto
        $imagenes = $this->buscarImagenesParaProducto($nombreProducto);
        
        if (!$imagenes) {
            $this->command->warn("⚠️  No se encontraron imágenes para: {$producto->nombre}");
            return;
        }

        try {
            // Agregar imagen principal
            if (!empty($imagenes['images'])) {
                $this->agregarImagenPrincipal($producto, $imagenes['images'][0]);
            }

            // Agregar galería de imágenes
            if (!empty($imagenes['gallery'])) {
                $this->agregarGaleriaImagenes($producto, $imagenes['gallery']);
            }

            $this->command->info("✅ Imágenes agregadas para: {$producto->nombre}");
            
        } catch (\Exception $e) {
            $this->command->error("❌ Error procesando {$producto->nombre}: {$e->getMessage()}");
        }
    }

    /**
     * Buscar imágenes para un producto basándose en su nombre
     */
    private function buscarImagenesParaProducto(string $nombreProducto): ?array
    {
        // Buscar coincidencia exacta primero
        if (isset($this->imagenesProductos[$nombreProducto])) {
            return $this->imagenesProductos[$nombreProducto];
        }

        // Buscar coincidencia parcial
        foreach ($this->imagenesProductos as $key => $imagenes) {
            if (Str::contains($nombreProducto, explode(' ', $key))) {
                return $imagenes;
            }
        }

        return null;
    }

    /**
     * Agregar imagen principal al producto
     */
    private function agregarImagenPrincipal(Producto $producto, string $url): void
    {
        // Verificar si ya tiene imagen principal
        if ($producto->getFirstMedia('images')) {
            $this->command->info("ℹ️  {$producto->nombre} ya tiene imagen principal, omitiendo...");
            return;
        }

        $this->agregarImagenDesdeUrl($producto, $url, 'images', 'imagen-principal');
    }

    /**
     * Agregar galería de imágenes al producto
     */
    private function agregarGaleriaImagenes(Producto $producto, array $urls): void
    {
        // Verificar si ya tiene galería
        if ($producto->getMedia('gallery')->count() > 0) {
            $this->command->info("ℹ️  {$producto->nombre} ya tiene galería, omitiendo...");
            return;
        }

        foreach ($urls as $index => $url) {
            $this->agregarImagenDesdeUrl($producto, $url, 'gallery', "galeria-{$index}");
        }
    }

    /**
     * Agregar imagen desde URL usando Spatie Media Library
     */
    private function agregarImagenDesdeUrl(Producto $producto, string $url, string $collection, string $name): void
    {
        try {
            $producto
                ->addMediaFromUrl($url)
                ->usingName($name)
                ->usingFileName($this->generarNombreArchivo($producto, $name))
                ->toMediaCollection($collection);
                
        } catch (\Exception $e) {
            $this->command->warn("⚠️  Error agregando imagen {$name} para {$producto->nombre}: {$e->getMessage()}");
        }
    }

    /**
     * Generar nombre de archivo único
     */
    private function generarNombreArchivo(Producto $producto, string $name): string
    {
        $slug = Str::slug($producto->nombre);
        $timestamp = now()->format('YmdHis');
        return "{$slug}-{$name}-{$timestamp}.jpg";
    }
}