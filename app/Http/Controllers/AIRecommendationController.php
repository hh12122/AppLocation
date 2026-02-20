<?php

namespace App\Http\Controllers;

use App\Services\AIRecommendationService;
use App\Models\Recommendation;
use App\Models\RecommendationFeedback;
use App\Models\Rental;
use App\Models\SearchHistory;
use App\Models\TrendingItem;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AIRecommendationController extends Controller
{
    private AIRecommendationService $aiService;

    public function __construct(AIRecommendationService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Get personalized recommendations for the authenticated user
     */
    public function getPersonalizedRecommendations(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'recommendations' => [],
                'message' => 'Connectez-vous pour des recommandations personnalisées'
            ]);
        }

        $type = $request->get('type', 'all');
        $limit = $request->get('limit', 10);

        $recommendations = $this->aiService->generatePersonalizedRecommendations($user, $type, $limit);

        // Load entities with relationships
        $enrichedRecommendations = $recommendations->map(function ($rec) {
            $entity = $rec->getEntity();
            if ($entity) {
                return [
                    'id' => $rec->id,
                    'type' => $rec->entity_type,
                    'entity' => $entity,
                    'score' => $rec->score,
                    'reason' => $rec->reason,
                    'recommendation_type' => $rec->recommendation_type,
                ];
            }
            return null;
        })->filter();

        return response()->json([
            'recommendations' => $enrichedRecommendations->values(),
            'count' => $enrichedRecommendations->count(),
        ]);
    }

    /**
     * Get trending items
     */
    public function getTrendingItems(Request $request)
    {
        $type = $request->get('type', 'all');
        $location = $request->get('location');
        $limit = $request->get('limit', 10);

        $trending = $this->aiService->getTrendingItems($type, $location, $limit);

        return response()->json([
            'trending' => $trending,
            'count' => $trending->count(),
            'period' => 'daily',
        ]);
    }

    /**
     * Get search suggestions
     */
    public function getSearchSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');
        $user = Auth::user();

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = $this->aiService->getSearchSuggestions($query, $type, $user);

        return response()->json([
            'suggestions' => $suggestions,
            'query' => $query,
        ]);
    }

    /**
     * Track user activity
     */
    public function trackActivity(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false]);
        }

        $validated = $request->validate([
            'activity_type' => 'required|in:view,search,click,book,favorite,review',
            'entity_type' => 'required|in:vehicle,property,equipment',
            'entity_id' => 'required|integer',
            'metadata' => 'nullable|array',
        ]);

        $this->aiService->trackActivity(
            $user,
            $validated['activity_type'],
            $validated['entity_type'],
            $validated['entity_id'],
            $validated['metadata'] ?? []
        );

        return response()->json(['success' => true]);
    }

    /**
     * Track search
     */
    public function trackSearch(Request $request)
    {
        $validated = $request->validate([
            'search_query' => 'required|string',
            'search_type' => 'required|in:vehicles,properties,equipment,global',
            'filters' => 'nullable|array',
            'results_count' => 'nullable|integer',
        ]);

        $searchHistory = SearchHistory::create([
            'user_id' => Auth::id(),
            'search_query' => $validated['search_query'],
            'search_type' => $validated['search_type'],
            'filters' => $validated['filters'] ?? [],
            'results_count' => $validated['results_count'] ?? 0,
            'session_id' => session()->getId(),
        ]);

        return response()->json([
            'success' => true,
            'search_id' => $searchHistory->id,
        ]);
    }

    /**
     * Mark search as successful
     */
    public function markSearchSuccess(Request $request, $searchId)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:vehicle,property,equipment',
            'item_id' => 'required|integer',
        ]);

        $search = SearchHistory::find($searchId);

        if ($search && (!$search->user_id || $search->user_id === Auth::id())) {
            $search->markAsSuccessful($validated['item_type'], $validated['item_id']);
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Mark recommendation as viewed
     */
    public function markRecommendationViewed($recommendationId)
    {
        $recommendation = Recommendation::where('id', $recommendationId)
            ->where('user_id', Auth::id())
            ->first();

        if ($recommendation) {
            $recommendation->markAsViewed();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Mark recommendation as clicked
     */
    public function markRecommendationClicked($recommendationId)
    {
        $recommendation = Recommendation::where('id', $recommendationId)
            ->where('user_id', Auth::id())
            ->first();

        if ($recommendation) {
            $recommendation->markAsClicked();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Mark recommendation as converted
     */
    public function markRecommendationConverted($recommendationId)
    {
        $recommendation = Recommendation::where('id', $recommendationId)
            ->where('user_id', Auth::id())
            ->first();

        if ($recommendation) {
            $recommendation->markAsConverted();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Provide feedback on a recommendation
     */
    public function provideFeedback(Request $request, $recommendationId)
    {
        $validated = $request->validate([
            'feedback_type' => 'required|in:helpful,not_helpful,irrelevant,already_seen',
            'comment' => 'nullable|string|max:500',
        ]);

        $recommendation = Recommendation::where('id', $recommendationId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$recommendation) {
            return response()->json(['success' => false], 404);
        }

        RecommendationFeedback::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'recommendation_id' => $recommendationId,
            ],
            [
                'feedback_type' => $validated['feedback_type'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Show AI recommendations dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();

        // Get user stats
        $stats = [
            'total_recommendations' => Recommendation::forUser($user->id)->count(),
            'viewed_recommendations' => Recommendation::forUser($user->id)->where('is_viewed', true)->count(),
            'conversions' => Recommendation::forUser($user->id)->where('is_converted', true)->count(),
            'recent_searches' => SearchHistory::forUser($user->id)->recent(7)->count(),
            'totalVehicles' => Vehicle::forOwner($user->id)->count(),
            'activeRentals' => Rental::forUser($user->id)->count()  ,
            'totalEarnings' => Rental::forUser($user->id)->where('status', 'completed')->sum('total_price'),
            'completedRentals' => Rental::forUser($user->id)->where('status', 'completed')->count(),
        ];
        // Get recent recommendations
        $recentRecommendations = Recommendation::forUser($user->id)
            ->active()
            ->topScored(5)
            ->get()
            ->map(function ($rec) {
                return [
                    'id' => $rec->id,
                    'entity' => $rec->getEntity(),
                    'type' => $rec->entity_type,
                    'score' => $rec->score,
                    'reason' => $rec->reason,
                    'is_viewed' => $rec->is_viewed,
                    'is_clicked' => $rec->is_clicked,
                ];
            });

        // Get trending items
        $trending = TrendingItem::forPeriod(today())
            ->topTrending(5)
            ->get()
            ->map(function ($item) {
                return [
                    'entity' => $item->getEntity(),
                    'type' => $item->entity_type,
                    'score' => $item->trend_score,
                    'views' => $item->view_count,
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentRecommendations' => $recentRecommendations,
            'trending' => $trending,
        ]);
    }
}
