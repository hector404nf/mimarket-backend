<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_categoria,
            'id_categoria' => $this->id_categoria,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion' => $this->description,
            'description' => $this->description,
            'icono' => $this->icono,
            'icono_url' => $this->icono_url,
            'icono_thumb_url' => $this->icono_thumb_url,
            'activo' => $this->activo,
            'orden' => $this->orden,
            'productos_count' => $this->when(isset($this->productos_count), $this->productos_count),
            'created_at' => $this->fecha_creacion?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}