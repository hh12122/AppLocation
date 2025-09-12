<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PropertyImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'image_path',
        'alt_text',
        'image_type',
        'sort_order',
        'is_primary',
        'is_featured',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Get the property that owns the image.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the full URL for the image.
     */
    public function getUrlAttribute(): string
    {
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        return Storage::url($this->image_path);
    }

    /**
     * Get image type label.
     */
    public function getImageTypeLabel(): string
    {
        $labels = [
            'exterior' => 'Extérieur',
            'interior' => 'Intérieur',
            'bedroom' => 'Chambre',
            'bathroom' => 'Salle de bain',
            'kitchen' => 'Cuisine',
            'living_room' => 'Salon',
            'dining_room' => 'Salle à manger',
            'balcony' => 'Balcon',
            'terrace' => 'Terrasse',
            'garden' => 'Jardin',
            'parking' => 'Parking',
            'building' => 'Immeuble',
            'neighborhood' => 'Quartier',
            'amenity' => 'Équipement',
            'other' => 'Autre',
        ];

        return $labels[$this->image_type] ?? $this->image_type;
    }

    /**
     * Set as primary image (removes primary status from others).
     */
    public function setAsPrimary(): void
    {
        // Remove primary status from other images
        static::where('property_id', $this->property_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // When deleting, clean up the file
        static::deleting(function (PropertyImage $image) {
            if (Storage::exists($image->image_path)) {
                Storage::delete($image->image_path);
            }
        });

        // When creating the first image, make it primary
        static::created(function (PropertyImage $image) {
            $imagesCount = static::where('property_id', $image->property_id)->count();
            if ($imagesCount === 1) {
                $image->update(['is_primary' => true]);
            }
        });
    }
}