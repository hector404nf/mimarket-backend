<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Cviebrock\EloquentSluggable\Sluggable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\User;
use App\Models\Tienda;
use App\Models\Categoria;

class Producto extends Model implements HasMedia
{
    use HasFactory, Sluggable, InteractsWithMedia;

    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    public $timestamps = true;

    protected $fillable = [
        'id_tienda',
        'user_id',
        'id_categoria',
        'nombre',
        'slug',
        'descripcion',
        'precio',
        'cantidad_stock',
        'estado',
        'destacado',
        'peso',
        'dimensiones',
        'marca',
        'modelo',
        'condicion',
        'tipo_vendedor',
        'activo'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'cantidad_stock' => 'integer',
        'peso' => 'decimal:3',
        'activo' => 'boolean',
        'destacado' => 'boolean'
    ];

    /**
     * Return the sluggable configuration array for this model.
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'nombre',
                'unique' => true,
                'separator' => '-',
                'includeTrashed' => false,
            ]
        ];
    }

    // Relaciones
    public function tienda(): BelongsTo
    {
        return $this->belongsTo(Tienda::class, 'id_tienda', 'id_tienda');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->singleFile();

        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    /**
     * Register media conversions
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('images', 'gallery');

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->sharpen(10)
            ->performOnCollections('images', 'gallery');
    }

    /**
     * Get the main image URL
     */
    public function getMainImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('images');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get the main image thumbnail URL
     */
    public function getMainImageThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('images');
        return $media ? $media->getUrl('thumb') : null;
    }

    /**
     * Get the main image media ID
     */
    public function getMainImageMediaIdAttribute(): ?int
    {
        $media = $this->getFirstMedia('images');
        return $media ? $media->id : null;
    }

    /**
     * Get all gallery images
     */
    public function getGalleryImagesAttribute(): array
    {
        return $this->getMedia('gallery')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'preview_url' => $media->getUrl('preview'),
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
            ];
        })->toArray();
    }
}
