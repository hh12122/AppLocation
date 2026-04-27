import { ref, computed } from 'vue';

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const fetchApi = async (url: string, options: RequestInit = {}) => {
  const headers = new Headers(options.headers || {});
  headers.set('Accept', 'application/json');
  headers.set('X-Requested-With', 'XMLHttpRequest');
  
  if (options.method && options.method.toUpperCase() !== 'GET') {
    headers.set('Content-Type', 'application/json');
    headers.set('X-CSRF-TOKEN', getCsrfToken());
  }

  const response = await fetch(url, {
    ...options,
    headers,
    credentials: 'include'
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => null);
    throw { response: { status: response.status, data: errorData } };
  }

  return response.json();
};

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
      const url = new URL('/api/ai/recommendations', window.location.origin);
      url.searchParams.append('type', type);
      url.searchParams.append('limit', limit.toString());
      const data = await fetchApi(url.toString());
      recommendations.value = data.recommendations;
      return data.recommendations;
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
      const url = new URL('/api/ai/trending', window.location.origin);
      url.searchParams.append('type', type);
      if (location) url.searchParams.append('location', location);
      url.searchParams.append('limit', limit.toString());
      const data = await fetchApi(url.toString());
      trending.value = data.trending;
      return data.trending;
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
      const url = new URL('/api/ai/search-suggestions', window.location.origin);
      url.searchParams.append('q', query);
      url.searchParams.append('type', type);
      const data = await fetchApi(url.toString());
      searchSuggestions.value = data.suggestions;
      return data.suggestions;
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
      await fetchApi('/api/ai/track-activity', {
        method: 'POST',
        body: JSON.stringify({
          activity_type: activityType,
          entity_type: entityType,
          entity_id: entityId,
          metadata
        })
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
      const data = await fetchApi('/api/ai/track-search', {
        method: 'POST',
        body: JSON.stringify({
          search_query: searchQuery,
          search_type: searchType,
          filters,
          results_count: resultsCount
        })
      });
      lastSearchId.value = data.search_id;
      return data.search_id;
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
      await fetchApi(`/api/ai/search/${id}/success`, {
        method: 'POST',
        body: JSON.stringify({
          item_type: itemType,
          item_id: itemId
        })
      });
    } catch (err) {
      console.error('Error marking search success:', err);
    }
  };

  // Track recommendation interactions
  const markRecommendationViewed = async (recommendationId: number) => {
    try {
      await fetchApi(`/api/ai/recommendations/${recommendationId}/viewed`, { method: 'POST' });
    } catch (err) {
      console.error('Error marking recommendation viewed:', err);
    }
  };

  const markRecommendationClicked = async (recommendationId: number) => {
    try {
      await fetchApi(`/api/ai/recommendations/${recommendationId}/clicked`, { method: 'POST' });
    } catch (err) {
      console.error('Error marking recommendation clicked:', err);
    }
  };

  const markRecommendationConverted = async (recommendationId: number) => {
    try {
      await fetchApi(`/api/ai/recommendations/${recommendationId}/converted`, { method: 'POST' });
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
      await fetchApi(`/api/ai/recommendations/${recommendationId}/feedback`, {
        method: 'POST',
        body: JSON.stringify({
          feedback_type: feedbackType,
          comment
        })
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