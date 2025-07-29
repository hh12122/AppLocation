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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewee_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['vehicle', 'owner', 'renter']);
            $table->integer('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->json('criteria_ratings')->nullable(); // cleanliness, communication, etc.
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            // Ensure one review per rental per reviewer
            $table->unique(['rental_id', 'reviewer_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
