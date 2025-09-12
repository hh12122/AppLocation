<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->enum('image_type', [
                'exterior',      // Extérieur
                'interior',      // Intérieur général
                'bedroom',       // Chambre
                'bathroom',      // Salle de bain
                'kitchen',       // Cuisine
                'living_room',   // Salon
                'dining_room',   // Salle à manger
                'balcony',       // Balcon
                'terrace',       // Terrasse
                'garden',        // Jardin
                'parking',       // Parking
                'building',      // Immeuble
                'neighborhood',  // Quartier
                'amenity',       // Équipement
                'other'
            ])->default('interior');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false); // Image principale
            $table->boolean('is_featured')->default(false); // Image mise en avant
            $table->timestamps();
            
            $table->index(['property_id', 'sort_order']);
            $table->index(['property_id', 'is_primary']);
            $table->index(['property_id', 'image_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_images');
    }
};