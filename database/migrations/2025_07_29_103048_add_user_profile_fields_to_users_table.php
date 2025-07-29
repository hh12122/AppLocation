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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('FR');
            $table->date('date_of_birth')->nullable();
            $table->string('driving_license_number')->nullable();
            $table->date('driving_license_expiry')->nullable();
            $table->boolean('is_owner')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'bio', 'avatar', 'address', 'city', 'postal_code', 
                'country', 'date_of_birth', 'driving_license_number', 
                'driving_license_expiry', 'is_owner', 'is_verified', 
                'rating', 'rating_count'
            ]);
        });
    }
};
