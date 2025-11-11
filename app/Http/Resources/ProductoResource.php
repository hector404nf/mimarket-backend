<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_producto' => $this->id_producto,
            'id_tienda' => $this->id_tienda,
            'user_id' => $this->user_id,
            'id_categoria' => $this->id_categoria,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'precio_oferta' => $this->precio_oferta,
            'stock' => $this->cantidad_stock,
            'sku' => $this->sku ?? '',
            'peso' => $this->peso,
            'dimensiones' => $this->dimensiones,
            // Tipo de venta
            'tipo_vendedor' => $this->tipo_vendedor,
            'activo' => $this->activo,
            'destacado' => $this->destacado,
            'calificacion_promedio' => $this->calificacion_promedio ?? 0,
            'total_ventas' => $this->total_ventas ?? 0,
            'fecha_creacion' => $this->created_at?->toISOString(),
            'fecha_actualizacion' => $this->updated_at?->toISOString(),
            
            // URLs de imágenes usando Spatie Media Library
            'imagen_principal' => $this->main_image_url,
            'imagen_principal_thumb' => $this->main_image_thumb_url,
            'imagen_principal_media_id' => $this->main_image_media_id,
            'imagenes_adicionales' => $this->gallery_images,
            
            // Relaciones
            'tienda' => $this->whenLoaded('tienda', function () {
                return [
                    'id_tienda' => $this->tienda->id_tienda,
                    'nombre_tienda' => $this->tienda->nombre_tienda,
                    'descripcion' => $this->tienda->descripcion,
                    'logo' => $this->tienda->logo,
                    'calificacion_promedio' => $this->tienda->calificacion_promedio ?? 0,
                ];
            }),
            
            'categoria' => $this->whenLoaded('categoria', function () {
                return [
                    'id_categoria' => $this->categoria->id_categoria,
                    'nombre' => $this->categoria->nombre,
                    'descripcion' => $this->categoria->descripcion,
                ];
            }),
            
            'usuario' => $this->whenLoaded('usuario', function () {
                return [
                    'id' => $this->usuario->id,
                    'name' => $this->usuario->name,
                    'email' => $this->usuario->email,
                ];
            }),
        ];
    }
}
