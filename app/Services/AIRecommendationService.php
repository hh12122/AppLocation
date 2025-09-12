<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Property;
use App\Models\Equipment;
use App\Models\UserActivity;
use App\Models\UserPreference;
use App\Models\Recommendation;
use App\Models\TrendingItem;
use App\Models\SearchHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AIRecommendationService
{
    private array $weights = [
        'view' => 1,
        'click' => 2,
        'favorite' => 3,
        'book' => 5,
        'review' => 4,
        'search' => 1.5,
    ];

    /**
     * Generate personalized recommendations for a user
     */
    public function generatePersonalizedRecommendations(User $user, string $type = 'all', int $limit = 10): Collection
    {
        // Clear old recommendations
        $this->clearExpiredRecommendations($user);

        $recommendations = collect();

        // Get user preferences
        $preferences = $this->analyzeUserPreferences($user);

        // Generate recommendations based on different strategies
        if ($type === 'all' || $type === 'vehicles') {
            $recommendations = $recommendations->merge(
                $this->recommendVehicles($user, $preferences, $limit)
            );
        }

        if ($type === 'all' || $type === 'properties') {
            $recommendations = $recommendations->merge(
                $this->recommendProperties($user, $preferences, $limit)
            );
        }

        if ($type === 'all' || $type === 'equipment') {
            $recommendations = $recommendations->merge(
                $this->recommendEquipment($user, $preferences, $limit)
            );
        }

        // Sort by score and limit
        return $recommendations->sortByDesc('score')->take($limit);
    }

    /**
     * Recommend vehicles based on user behavior and preferences
     */
    private function recommendVehicles(User $user, array $preferences, int $limit): Collection
    {
        $recommendations = collect();

        // Collaborative filtering - items liked by similar users
        $similarUsers = $this->findSimilarUsers($user, 'vehicle');
        $collaborativeItems = $this->getCollaborativeRecommendations($user, $similarUsers, Vehicle::class, $limit / 2);
        
        foreach ($collaborativeItems as $item) {
            $recommendations->push($this->createRecommendation(
                $user,
                'vehicle',
                $item->id,
                $item->score,
                'personalized',
                'Basé sur des utilisateurs similaires'
            ));
        }

        // Content-based filtering - similar to what user has viewed/booked
        $contentBasedItems = $this->getContentBasedRecommendations($user, Vehicle::class, $preferences, $limit / 2);
        
        foreach ($contentBasedItems as $item) {
            $recommendations->push($this->createRecommendation(
                $user,
                'vehicle',
                $item->id,
                $item->score,
                'similar',
                'Similaire à vos intérêts'
            ));
        }

        // Location-based recommendations
        if (isset($preferences['preferred_cities'])) {
            $locationItems = Vehicle::whereIn('city', $preferences['preferred_cities'])
                ->where('is_available', true)
                ->inRandomOrder()
                ->limit($limit / 4)
                ->get();
            
            foreach ($locationItems as $item) {
                $score = $this->calculateLocationScore($item, $preferences);
                $recommendations->push($this->createRecommendation(
                    $user,
                    'vehicle',
                    $item->id,
                    $score,
                    'location_based',
                    'Dans votre zone préférée'
                ));
            }
        }

        return $recommendations;
    }

    /**
     * Recommend properties based on user behavior
     */
    private function recommendProperties(User $user, array $preferences, int $limit): Collection
    {
        $recommendations = collect();

        // Get user's property preferences
        $propertyPrefs = UserPreference::where('user_id', $user->id)
            ->where('preference_type', 'property_type')
            ->pluck('preference_value', 'preference_key');

        // Find properties matching preferences
        $query = Property::query()->where('is_available', true);

        if ($propertyPrefs->isNotEmpty()) {
            $query->where(function ($q) use ($propertyPrefs) {
                foreach ($propertyPrefs as $key => $value) {
                    $q->orWhere($key, $value);
                }
            });
        }

        // Price range preference
        if (isset($preferences['price_range'])) {
            $query->whereBetween('price_per_night', $preferences['price_range']);
        }

        $properties = $query->inRandomOrder()->limit($limit)->get();

        foreach ($properties as $property) {
            $score = $this->calculatePropertyScore($property, $user, $preferences);
            $recommendations->push($this->createRecommendation(
                $user,
                'property',
                $property->id,
                $score,
                'personalized',
                'Correspond à vos critères'
            ));
        }

        return $recommendations;
    }

    /**
     * Recommend equipment based on user behavior
     */
    private function recommendEquipment(User $user, array $preferences, int $limit): Collection
    {
        $recommendations = collect();

        // Get user's equipment category preferences
        $categoryPrefs = UserActivity::where('user_id', $user->id)
            ->where('entity_type', 'equipment')
            ->where('activity_type', 'view')
            ->join('equipment', 'user_activities.entity_id', '=', 'equipment.id')
            ->select('equipment.category', DB::raw('COUNT(*) as count'))
            ->groupBy('equipment.category')
            ->orderByDesc('count')
            ->pluck('count', 'category');

        // Find equipment in preferred categories
        $query = Equipment::query()->where('is_available', true);

        if ($categoryPrefs->isNotEmpty()) {
            $topCategory = $categoryPrefs->keys()->first();
            $query->where('category', $topCategory);
        }

        $equipment = $query->inRandomOrder()->limit($limit)->get();

        foreach ($equipment as $item) {
            $score = $this->calculateEquipmentScore($item, $user, $preferences);
            $recommendations->push($this->createRecommendation(
                $user,
                'equipment',
                $item->id,
                $score,
                'personalized',
                'Dans vos catégories préférées'
            ));
        }

        return $recommendations;
    }

    /**
     * Find similar users using collaborative filtering
     */
    private function findSimilarUsers(User $user, string $entityType, int $limit = 10): Collection
    {
        // Get user's interactions
        $userInteractions = UserActivity::where('user_id', $user->id)
            ->where('entity_type', $entityType)
            ->whereIn('activity_type', ['view', 'favorite', 'book'])
            ->pluck('entity_id')
            ->toArray();

        if (empty($userInteractions)) {
            return collect();
        }

        // Find users with similar interactions
        $similarUsers = UserActivity::where('user_id', '!=', $user->id)
            ->where('entity_type', $entityType)
            ->whereIn('entity_id', $userInteractions)
            ->whereIn('activity_type', ['view', 'favorite', 'book'])
            ->select('user_id', DB::raw('COUNT(*) as similarity_score'))
            ->groupBy('user_id')
            ->orderByDesc('similarity_score')
            ->limit($limit)
            ->get();

        return $similarUsers;
    }

    /**
     * Get collaborative filtering recommendations
     */
    private function getCollaborativeRecommendations(User $user, Collection $similarUsers, string $modelClass, int $limit): Collection
    {
        if ($similarUsers->isEmpty()) {
            return collect();
        }

        $userIds = $similarUsers->pluck('user_id')->toArray();
        
        // Get items that similar users interacted with but current user hasn't
        $userItems = UserActivity::where('user_id', $user->id)
            ->where('entity_type', strtolower(class_basename($modelClass)))
            ->pluck('entity_id')
            ->toArray();

        $recommendations = UserActivity::whereIn('user_id', $userIds)
            ->where('entity_type', strtolower(class_basename($modelClass)))
            ->whereNotIn('entity_id', $userItems)
            ->whereIn('activity_type', ['view', 'favorite', 'book'])
            ->select('entity_id', DB::raw('
                SUM(CASE activity_type 
                    WHEN "book" THEN 5
                    WHEN "favorite" THEN 3
                    WHEN "view" THEN 1
                    ELSE 0
                END) as score
            '))
            ->groupBy('entity_id')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return $recommendations;
    }

    /**
     * Get content-based recommendations
     */
    private function getContentBasedRecommendations(User $user, string $modelClass, array $preferences, int $limit): Collection
    {
        // Get user's recent interactions
        $recentItems = UserActivity::where('user_id', $user->id)
            ->where('entity_type', strtolower(class_basename($modelClass)))
            ->whereIn('activity_type', ['view', 'favorite', 'book'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->pluck('entity_id');

        if ($recentItems->isEmpty()) {
            return collect();
        }

        // Get features of interacted items
        $items = $modelClass::whereIn('id', $recentItems)->get();
        
        // Extract common features
        $commonFeatures = $this->extractCommonFeatures($items, $modelClass);
        
        // Find similar items
        $query = $modelClass::query()
            ->whereNotIn('id', $recentItems)
            ->where('is_available', true);

        // Apply feature-based filtering
        foreach ($commonFeatures as $feature => $value) {
            if (is_array($value)) {
                $query->whereIn($feature, $value);
            } else {
                $query->where($feature, $value);
            }
        }

        $similarItems = $query->limit($limit)->get();
        
        // Calculate similarity scores
        $recommendations = collect();
        foreach ($similarItems as $item) {
            $score = $this->calculateSimilarityScore($item, $items);
            $recommendations->push((object)[
                'id' => $item->id,
                'score' => $score
            ]);
        }

        return $recommendations->sortByDesc('score')->take($limit);
    }

    /**
     * Extract common features from items
     */
    private function extractCommonFeatures(Collection $items, string $modelClass): array
    {
        $features = [];

        if ($modelClass === Vehicle::class) {
            // Most common vehicle type
            $types = $items->pluck('vehicle_type')->countBy();
            if ($types->isNotEmpty()) {
                $features['vehicle_type'] = $types->sortDesc()->keys()->take(2)->toArray();
            }

            // Price range
            $avgPrice = $items->avg('daily_rate');
            $features['daily_rate'] = [$avgPrice * 0.7, $avgPrice * 1.3];

            // Common cities
            $cities = $items->pluck('city')->unique()->take(3)->toArray();
            if (!empty($cities)) {
                $features['city'] = $cities;
            }
        }

        return $features;
    }

    /**
     * Calculate similarity score between items
     */
    private function calculateSimilarityScore($item, Collection $referenceItems): float
    {
        $score = 0;
        $weights = [
            'exact_match' => 1.0,
            'category_match' => 0.7,
            'price_similarity' => 0.5,
            'location_match' => 0.6,
            'feature_overlap' => 0.4,
        ];

        foreach ($referenceItems as $refItem) {
            // Category/type matching
            if (isset($item->vehicle_type) && $item->vehicle_type === $refItem->vehicle_type) {
                $score += $weights['category_match'];
            }
            if (isset($item->category) && $item->category === $refItem->category) {
                $score += $weights['category_match'];
            }

            // Location matching
            if ($item->city === $refItem->city) {
                $score += $weights['location_match'];
            }

            // Price similarity (within 20%)
            if (isset($item->daily_rate) && isset($refItem->daily_rate)) {
                $priceDiff = abs($item->daily_rate - $refItem->daily_rate) / $refItem->daily_rate;
                if ($priceDiff <= 0.2) {
                    $score += $weights['price_similarity'] * (1 - $priceDiff * 5);
                }
            }
        }

        // Normalize score
        return min($score / count($referenceItems), 1.0);
    }

    /**
     * Analyze user preferences from their behavior
     */
    private function analyzeUserPreferences(User $user): array
    {
        $preferences = [];

        // Analyze price preferences
        $bookings = DB::table('rentals')
            ->where('renter_id', $user->id)
            ->join('vehicles', 'rentals.vehicle_id', '=', 'vehicles.id')
            ->select('vehicles.daily_rate')
            ->get();

        if ($bookings->isNotEmpty()) {
            $avgPrice = $bookings->avg('daily_rate');
            $preferences['price_range'] = [
                $avgPrice * 0.7,
                $avgPrice * 1.3
            ];
        }

        // Analyze location preferences
        $cities = UserActivity::where('user_id', $user->id)
            ->where('activity_type', 'view')
            ->join('vehicles', function($join) {
                $join->on('user_activities.entity_id', '=', 'vehicles.id')
                     ->where('user_activities.entity_type', '=', 'vehicle');
            })
            ->select('vehicles.city', DB::raw('COUNT(*) as count'))
            ->groupBy('vehicles.city')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('city')
            ->toArray();

        if (!empty($cities)) {
            $preferences['preferred_cities'] = $cities;
        }

        // Store preferences for future use
        $this->storeUserPreferences($user, $preferences);

        return $preferences;
    }

    /**
     * Store user preferences
     */
    private function storeUserPreferences(User $user, array $preferences): void
    {
        foreach ($preferences as $type => $value) {
            UserPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'preference_type' => $type,
                    'preference_key' => $type,
                ],
                [
                    'preference_value' => json_encode($value),
                    'weight' => 0.7,
                    'confidence' => 0.6,
                    'last_interaction_at' => now(),
                ]
            );
        }
    }

    /**
     * Calculate location-based score
     */
    private function calculateLocationScore($item, array $preferences): float
    {
        $score = 0.5; // Base score

        if (isset($preferences['preferred_cities']) && in_array($item->city, $preferences['preferred_cities'])) {
            $score += 0.3;
        }

        // Boost if item is in a trending location
        $trending = TrendingItem::where('entity_type', strtolower(class_basename($item)))
            ->where('entity_id', $item->id)
            ->where('trend_type', 'daily')
            ->where('period_date', today())
            ->first();

        if ($trending && $trending->trend_score > 0.5) {
            $score += 0.2;
        }

        return min($score, 1.0);
    }

    /**
     * Calculate property-specific score
     */
    private function calculatePropertyScore(Property $property, User $user, array $preferences): float
    {
        $score = 0.5;

        // Price match
        if (isset($preferences['price_range'])) {
            if ($property->price_per_night >= $preferences['price_range'][0] && 
                $property->price_per_night <= $preferences['price_range'][1]) {
                $score += 0.2;
            }
        }

        // Rating boost
        if ($property->rating >= 4.5) {
            $score += 0.1;
        }

        // Location preference
        if (isset($preferences['preferred_cities']) && in_array($property->city, $preferences['preferred_cities'])) {
            $score += 0.2;
        }

        return min($score, 1.0);
    }

    /**
     * Calculate equipment-specific score
     */
    private function calculateEquipmentScore(Equipment $equipment, User $user, array $preferences): float
    {
        $score = 0.5;

        // Category preference boost
        $categoryPref = UserPreference::where('user_id', $user->id)
            ->where('preference_type', 'equipment_category')
            ->where('preference_key', $equipment->category)
            ->first();

        if ($categoryPref) {
            $score += $categoryPref->weight * 0.3;
        }

        // Price match
        if (isset($preferences['price_range']) && $equipment->daily_rate) {
            if ($equipment->daily_rate >= $preferences['price_range'][0] * 0.5 && 
                $equipment->daily_rate <= $preferences['price_range'][1] * 0.5) {
                $score += 0.2;
            }
        }

        return min($score, 1.0);
    }

    /**
     * Create and store a recommendation
     */
    private function createRecommendation(
        User $user, 
        string $entityType, 
        int $entityId, 
        float $score, 
        string $type, 
        string $reason
    ): Recommendation {
        return Recommendation::updateOrCreate(
            [
                'user_id' => $user->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'recommendation_type' => $type,
            ],
            [
                'score' => $score,
                'reason' => $reason,
                'factors' => json_encode([
                    'generated_at' => now(),
                    'algorithm' => 'hybrid',
                ]),
                'expires_at' => now()->addDays(7),
            ]
        );
    }

    /**
     * Clear expired recommendations
     */
    private function clearExpiredRecommendations(User $user): void
    {
        Recommendation::where('user_id', $user->id)
            ->where('expires_at', '<', now())
            ->delete();
    }

    /**
     * Track user activity for learning
     */
    public function trackActivity(
        User $user, 
        string $activityType, 
        string $entityType, 
        int $entityId, 
        array $metadata = []
    ): void {
        UserActivity::create([
            'user_id' => $user->id,
            'activity_type' => $activityType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => json_encode($metadata),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Update user preferences based on activity
        $this->updatePreferencesFromActivity($user, $activityType, $entityType, $entityId);
    }

    /**
     * Update preferences from user activity
     */
    private function updatePreferencesFromActivity(
        User $user, 
        string $activityType, 
        string $entityType, 
        int $entityId
    ): void {
        $weight = $this->weights[$activityType] ?? 1;

        // Get entity details
        $entity = null;
        switch ($entityType) {
            case 'vehicle':
                $entity = Vehicle::find($entityId);
                if ($entity) {
                    $this->updatePreference($user, 'vehicle_type', $entity->vehicle_type, $weight);
                    $this->updatePreference($user, 'vehicle_city', $entity->city, $weight);
                }
                break;
            case 'property':
                $entity = Property::find($entityId);
                if ($entity) {
                    $this->updatePreference($user, 'property_type', $entity->property_type, $weight);
                    $this->updatePreference($user, 'property_city', $entity->city, $weight);
                }
                break;
            case 'equipment':
                $entity = Equipment::find($entityId);
                if ($entity) {
                    $this->updatePreference($user, 'equipment_category', $entity->category, $weight);
                    $this->updatePreference($user, 'equipment_city', $entity->city, $weight);
                }
                break;
        }
    }

    /**
     * Update a specific preference
     */
    private function updatePreference(User $user, string $type, $value, float $weight): void
    {
        $preference = UserPreference::firstOrCreate(
            [
                'user_id' => $user->id,
                'preference_type' => $type,
                'preference_key' => is_string($value) ? $value : json_encode($value),
            ],
            [
                'preference_value' => json_encode($value),
                'weight' => 0.5,
                'confidence' => 0.5,
                'interaction_count' => 0,
            ]
        );

        // Update weight using exponential moving average
        $alpha = 0.3; // Learning rate
        $newWeight = ($alpha * $weight / 5) + ((1 - $alpha) * $preference->weight);
        
        $preference->update([
            'weight' => min($newWeight, 1.0),
            'confidence' => min($preference->confidence + 0.05, 1.0),
            'interaction_count' => $preference->interaction_count + 1,
            'last_interaction_at' => now(),
        ]);
    }

    /**
     * Get trending items
     */
    public function getTrendingItems(string $type = 'all', string $location = null, int $limit = 10): Collection
    {
        $query = TrendingItem::where('period_date', today())
            ->where('trend_type', 'daily')
            ->orderByDesc('trend_score');

        if ($type !== 'all') {
            $query->where('entity_type', $type);
        }

        if ($location) {
            $query->where('location', $location);
        }

        $trending = $query->limit($limit)->get();

        // Load actual entities
        $items = collect();
        foreach ($trending as $trend) {
            $entity = null;
            switch ($trend->entity_type) {
                case 'vehicle':
                    $entity = Vehicle::find($trend->entity_id);
                    break;
                case 'property':
                    $entity = Property::find($trend->entity_id);
                    break;
                case 'equipment':
                    $entity = Equipment::find($trend->entity_id);
                    break;
            }

            if ($entity) {
                $entity->trend_score = $trend->trend_score;
                $entity->trend_type = $trend->entity_type;
                $items->push($entity);
            }
        }

        return $items;
    }

    /**
     * Get search suggestions based on user history and popular searches
     */
    public function getSearchSuggestions(string $query, string $type = 'all', User $user = null): array
    {
        $suggestions = [];

        // Get user's recent searches if logged in
        if ($user) {
            $recentSearches = SearchHistory::where('user_id', $user->id)
                ->where('search_query', 'LIKE', $query . '%')
                ->where('has_interaction', true)
                ->orderByDesc('created_at')
                ->limit(3)
                ->pluck('search_query')
                ->toArray();

            $suggestions = array_merge($suggestions, $recentSearches);
        }

        // Get popular searches
        $popularSearches = DB::table('popular_searches')
            ->where('search_term', 'LIKE', $query . '%')
            ->where('period_date', '>=', now()->subDays(7))
            ->orderByDesc('effectiveness_score')
            ->orderByDesc('search_count')
            ->limit(5)
            ->pluck('search_term')
            ->toArray();

        $suggestions = array_merge($suggestions, $popularSearches);

        // Get entity-based suggestions
        if ($type === 'all' || $type === 'vehicles') {
            $vehicles = Vehicle::where('brand', 'LIKE', $query . '%')
                ->orWhere('model', 'LIKE', $query . '%')
                ->limit(3)
                ->pluck(DB::raw("CONCAT(brand, ' ', model) as name"))
                ->toArray();
            $suggestions = array_merge($suggestions, $vehicles);
        }

        // Remove duplicates and limit
        return array_slice(array_unique($suggestions), 0, 10);
    }

    /**
     * Update trending scores
     */
    public function updateTrendingScores(): void
    {
        $today = today();
        
        // Calculate trending for each entity type
        foreach (['vehicle', 'property', 'equipment'] as $entityType) {
            // Get activity counts for today
            $activities = UserActivity::where('entity_type', $entityType)
                ->whereDate('created_at', $today)
                ->select('entity_id', 'activity_type', DB::raw('COUNT(*) as count'))
                ->groupBy('entity_id', 'activity_type')
                ->get();

            // Group by entity
            $entityScores = [];
            foreach ($activities as $activity) {
                if (!isset($entityScores[$activity->entity_id])) {
                    $entityScores[$activity->entity_id] = [
                        'view_count' => 0,
                        'click_count' => 0,
                        'booking_count' => 0,
                        'favorite_count' => 0,
                    ];
                }

                switch ($activity->activity_type) {
                    case 'view':
                        $entityScores[$activity->entity_id]['view_count'] = $activity->count;
                        break;
                    case 'click':
                        $entityScores[$activity->entity_id]['click_count'] = $activity->count;
                        break;
                    case 'book':
                        $entityScores[$activity->entity_id]['booking_count'] = $activity->count;
                        break;
                    case 'favorite':
                        $entityScores[$activity->entity_id]['favorite_count'] = $activity->count;
                        break;
                }
            }

            // Calculate and store trending scores
            foreach ($entityScores as $entityId => $scores) {
                $trendScore = ($scores['view_count'] * 1) +
                             ($scores['click_count'] * 2) +
                             ($scores['favorite_count'] * 3) +
                             ($scores['booking_count'] * 5);

                // Normalize score (0-1)
                $trendScore = min($trendScore / 100, 1.0);

                TrendingItem::updateOrCreate(
                    [
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                        'trend_type' => 'daily',
                        'period_date' => $today,
                    ],
                    [
                        'view_count' => $scores['view_count'],
                        'click_count' => $scores['click_count'],
                        'booking_count' => $scores['booking_count'],
                        'favorite_count' => $scores['favorite_count'],
                        'trend_score' => $trendScore,
                    ]
                );
            }
        }
    }
}