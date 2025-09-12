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
        // User behavior tracking
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // view, search, click, book, favorite, review
            $table->string('entity_type'); // vehicle, property, equipment
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');
            
            $table->index(['user_id', 'activity_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        // Search history for better suggestions
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('search_query');
            $table->string('search_type'); // vehicles, properties, equipment, global
            $table->json('filters')->nullable(); // Applied filters
            $table->integer('results_count')->default(0);
            $table->boolean('has_interaction')->default(false); // Did user click on results
            $table->string('selected_item_type')->nullable();
            $table->unsignedBigInteger('selected_item_id')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('search_query');
            $table->index('created_at');
        });

        // AI-generated recommendations
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('recommendation_type'); // similar, trending, personalized, location_based, price_based
            $table->string('entity_type'); // vehicle, property, equipment
            $table->unsignedBigInteger('entity_id');
            $table->float('score', 8, 4); // Recommendation confidence score (0-1)
            $table->string('reason')->nullable(); // Why this was recommended
            $table->json('factors')->nullable(); // Factors that influenced the recommendation
            $table->boolean('is_viewed')->default(false);
            $table->boolean('is_clicked')->default(false);
            $table->boolean('is_converted')->default(false); // Led to booking
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'recommendation_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('score');
            $table->index('expires_at');
            $table->unique(['user_id', 'entity_type', 'entity_id', 'recommendation_type']);
        });

        // User preferences learned by AI
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('preference_type'); // category, brand, price_range, location, features
            $table->string('preference_key');
            $table->json('preference_value');
            $table->float('weight', 8, 4)->default(0.5); // Importance weight (0-1)
            $table->float('confidence', 8, 4)->default(0.5); // AI confidence in this preference
            $table->integer('interaction_count')->default(0);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'preference_type', 'preference_key']);
            $table->index(['user_id', 'preference_type']);
            $table->index('weight');
        });

        // Trending items tracking
        Schema::create('trending_items', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // vehicle, property, equipment
            $table->unsignedBigInteger('entity_id');
            $table->string('trend_type'); // daily, weekly, monthly, location_based
            $table->string('location')->nullable(); // City or region for location-based trends
            $table->integer('view_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('booking_count')->default(0);
            $table->integer('favorite_count')->default(0);
            $table->float('trend_score', 8, 4)->default(0); // Calculated trend score
            $table->date('period_date'); // The date this trend data is for
            $table->timestamps();
            
            $table->unique(['entity_type', 'entity_id', 'trend_type', 'period_date', 'location']);
            $table->index(['entity_type', 'trend_type', 'period_date']);
            $table->index('trend_score');
            $table->index('location');
        });

        // Popular search terms
        Schema::create('popular_searches', function (Blueprint $table) {
            $table->id();
            $table->string('search_term');
            $table->string('search_type'); // vehicles, properties, equipment
            $table->string('location')->nullable();
            $table->integer('search_count')->default(1);
            $table->integer('result_clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->float('effectiveness_score', 8, 4)->default(0); // Click-through rate
            $table->date('period_date');
            $table->timestamps();
            
            $table->unique(['search_term', 'search_type', 'location', 'period_date']);
            $table->index(['search_type', 'period_date']);
            $table->index('search_count');
            $table->index('effectiveness_score');
        });

        // AI model training data
        Schema::create('ai_training_data', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // collaborative_filtering, content_based, hybrid
            $table->string('data_type'); // user_item_interaction, item_features, user_features
            $table->json('input_features');
            $table->json('output_labels')->nullable();
            $table->float('confidence', 8, 4)->nullable();
            $table->boolean('is_validated')->default(false);
            $table->boolean('is_used_for_training')->default(false);
            $table->timestamps();
            
            $table->index(['model_type', 'data_type']);
            $table->index('is_used_for_training');
        });

        // Recommendation feedback
        Schema::create('recommendation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('recommendation_id')->constrained()->onDelete('cascade');
            $table->enum('feedback_type', ['helpful', 'not_helpful', 'irrelevant', 'already_seen']);
            $table->text('comment')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'recommendation_id']);
            $table->index('feedback_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendation_feedback');
        Schema::dropIfExists('ai_training_data');
        Schema::dropIfExists('popular_searches');
        Schema::dropIfExists('trending_items');
        Schema::dropIfExists('user_preferences');
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('search_histories');
        Schema::dropIfExists('user_activities');
    }
};