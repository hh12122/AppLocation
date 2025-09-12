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
        Schema::create('equipment_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->enum('image_type', [
                'main',              // Image principale
                'detail',            // Détail du produit
                'accessories',       // Accessoires inclus
                'size_guide',        // Guide des tailles
                'condition',         // État du produit
                'location',          // Lieu de récupération
                'usage',             // En utilisation
                'safety',            // Équipements de sécurité
                'manual',            // Manuel d'utilisation
                'certificate',       // Certificats/licences
                'other'
            ])->default('main');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            
            $table->index(['equipment_id', 'sort_order']);
            $table->index(['equipment_id', 'is_primary']);
            $table->index(['equipment_id', 'image_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_images');
    }
};