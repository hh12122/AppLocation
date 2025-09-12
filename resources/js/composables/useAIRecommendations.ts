import { ref, computed } from 'vue';
import axios from 'axios';

interface Recommendation {
  id: number;
  type: string;
  entity: any;
  score: number;
  reason: string;
  recommendation_type: string;
}

interface TrendingItem {
  entity: any;
  type: string;
  score: number;
  views: number;
}

export function useAIRecommendations() {
  const recommendations = ref<Recommendation[]>([]);
  const trending = ref<TrendingItem[]>([]);
  const searchSuggestions = ref<string[]>([]);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const lastSearchId = ref<number | null>(null);

  // Get personalized recommendations
  const getRecommendations = async (type: string = 'all', limit: number = 10) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await axios.get('/api/ai/recommendations', {
        params: { type, limit }
      });
      recommendations.value = response.data.recommendations;
      return response.data.recommendations;
    } catch (err) {
      error.value = 'Erreur lors du chargement des recommandations';
      console.error('Error fetching recommendations:', err);
      return [];
    } finally {
      isLoading.value = false;
    }
  };

  // Get trending items
  const getTrending = async (type: string = 'all', location?: string, limit: number = 10) => {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await axios.get('/api/ai/trending', {
        params: { type, location, limit }
      });
      trending.value = response.data.trending;
      return response.data.trending;
    } catch (err) {
      error.value = 'Erreur lors du chargement des tendances';
      console.error('Error fetching trending:', err);
      return [];
    } finally {
      isLoading.value = false;
    }
  };

  // Get search suggestions
  const getSearchSuggestions = async (query: string, type: string = 'all') => {
    if (query.length < 2) {
      searchSuggestions.value = [];
      return [];
    }

    try {
      const response = await axios.get('/api/ai/search-suggestions', {
        params: { q: query, type }
      });
      searchSuggestions.value = response.data.suggestions;
      return response.data.suggestions;
    } catch (err) {
      console.error('Error fetching suggestions:', err);
      return [];
    }
  };

  // Track user activity
  const trackActivity = async (
    activityType: string,
    entityType: string,
    entityId: number,
    metadata: Record<string, any> = {}
  ) => {
    try {
      await axios.post('/api/ai/track-activity', {
        activity_type: activityType,
        entity_type: entityType,
        entity_id: entityId,
        metadata
      });
    } catch (err) {
      console.error('Error tracking activity:', err);
    }
  };

  // Track search
  const trackSearch = async (
    searchQuery: string,
    searchType: string,
    filters: Record<string, any> = {},
    resultsCount: number = 0
  ) => {
    try {
      const response = await axios.post('/api/ai/track-search', {
        search_query: searchQuery,
        search_type: searchType,
        filters,
        results_count: resultsCount
      });
      lastSearchId.value = response.data.search_id;
      return response.data.search_id;
    } catch (err) {
      console.error('Error tracking search:', err);
      return null;
    }
  };

  // Mark search as successful
  const markSearchSuccess = async (itemType: string, itemId: number, searchId?: number) => {
    const id = searchId || lastSearchId.value;
    if (!id) return;

    try {
      await axios.post(`/api/ai/search/${id}/success`, {
        item_type: itemType,
        item_id: itemId
      });
    } catch (err) {
      console.error('Error marking search success:', err);
    }
  };

  // Track recommendation interactions
  const markRecommendationViewed = async (recommendationId: number) => {
    try {
      await axios.post(`/api/ai/recommendations/${recommendationId}/viewed`);
    } catch (err) {
      console.error('Error marking recommendation viewed:', err);
    }
  };

  const markRecommendationClicked = async (recommendationId: number) => {
    try {
      await axios.post(`/api/ai/recommendations/${recommendationId}/clicked`);
    } catch (err) {
      console.error('Error marking recommendation clicked:', err);
    }
  };

  const markRecommendationConverted = async (recommendationId: number) => {
    try {
      await axios.post(`/api/ai/recommendations/${recommendationId}/converted`);
    } catch (err) {
      console.error('Error marking recommendation converted:', err);
    }
  };

  // Provide feedback on recommendation
  const provideFeedback = async (
    recommendationId: number,
    feedbackType: 'helpful' | 'not_helpful' | 'irrelevant' | 'already_seen',
    comment?: string
  ) => {
    try {
      await axios.post(`/api/ai/recommendations/${recommendationId}/feedback`, {
        feedback_type: feedbackType,
        comment
      });
      return true;
    } catch (err) {
      console.error('Error providing feedback:', err);
      return false;
    }
  };

  // Auto-track view when component is mounted
  const trackView = (entityType: string, entityId: number) => {
    trackActivity('view', entityType, entityId);
  };

  // Auto-track click
  const trackClick = (entityType: string, entityId: number, recommendationId?: number) => {
    trackActivity('click', entityType, entityId);
    if (recommendationId) {
      markRecommendationClicked(recommendationId);
    }
  };

  // Track booking
  const trackBooking = (entityType: string, entityId: number, recommendationId?: number) => {
    trackActivity('book', entityType, entityId);
    if (recommendationId) {
      markRecommendationConverted(recommendationId);
    }
  };

  // Track favorite
  const trackFavorite = (entityType: string, entityId: number) => {
    trackActivity('favorite', entityType, entityId);
  };

  // Track review
  const trackReview = (entityType: string, entityId: number) => {
    trackActivity('review', entityType, entityId);
  };

  // Computed properties
  const hasRecommendations = computed(() => recommendations.value.length > 0);
  const hasTrending = computed(() => trending.value.length > 0);
  const topRecommendations = computed(() => recommendations.value.slice(0, 5));
  const topTrending = computed(() => trending.value.slice(0, 5));

  return {
    // State
    recommendations,
    trending,
    searchSuggestions,
    isLoading,
    error,
    lastSearchId,
    
    // Computed
    hasRecommendations,
    hasTrending,
    topRecommendations,
    topTrending,
    
    // Methods
    getRecommendations,
    getTrending,
    getSearchSuggestions,
    trackActivity,
    trackSearch,
    markSearchSuccess,
    markRecommendationViewed,
    markRecommendationClicked,
    markRecommendationConverted,
    provideFeedback,
    trackView,
    trackClick,
    trackBooking,
    trackFavorite,
    trackReview,
  };
}