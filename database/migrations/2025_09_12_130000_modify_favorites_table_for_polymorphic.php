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
        Schema::table('favorites', function (Blueprint $table) {
            // Add polymorphic columns
            $table->string('favoritable_type')->nullable()->after('user_id');
            $table->unsignedBigInteger('favoritable_id')->nullable()->after('favoritable_type');
            
            // Update existing records to use the polymorphic relationship
            // (This will be handled in a separate data migration if needed)
            
            // We'll keep the vehicle_id column for now to maintain compatibility
            // and will remove it in a separate migration after data is migrated
        });
        
        // Update existing records to use polymorphic relationship
        DB::statement("
            UPDATE favorites 
            SET favoritable_type = 'App\\\\Models\\\\Vehicle',
                favoritable_id = vehicle_id 
            WHERE vehicle_id IS NOT NULL
        ");
        
        Schema::table('favorites', function (Blueprint $table) {
            // Add composite index for polymorphic relationship
            $table->index(['favoritable_type', 'favoritable_id'], 'favorites_favoritable_index');
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_unique_polymorphic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex('favorites_favoritable_index');
            $table->dropUnique('favorites_unique_polymorphic');
            $table->dropColumn(['favoritable_type', 'favoritable_id']);
        });
    }
};