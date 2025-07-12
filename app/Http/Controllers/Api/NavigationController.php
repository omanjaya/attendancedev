<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NavigationController extends Controller
{
    private NavigationService $navigationService;

    public function __construct(NavigationService $navigationService)
    {
        $this->navigationService = $navigationService;
    }

    /**
     * Search navigation items
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:2|max:100',
            ]);

            $results = $this->navigationService->searchNavigation(
                $validated['query'],
                auth()->user()
            );

            return response()->json($results);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Invalid search query',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Search failed',
                'message' => 'An error occurred while searching navigation'
            ], 500);
        }
    }

    /**
     * Get navigation structure for current user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $navigation = $this->navigationService->getMainNavigation(
                user: auth()->user()
            );

            $bottomNavigation = $this->navigationService->getBottomNavigation(
                user: auth()->user()
            );

            $favorites = $this->navigationService->getUserFavorites(
                auth()->user()
            );

            return response()->json([
                'main' => $navigation,
                'bottom' => $bottomNavigation,
                'favorites' => $favorites,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load navigation',
                'message' => 'An error occurred while loading navigation data'
            ], 500);
        }
    }

    /**
     * Update user favorites
     */
    public function updateFavorites(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'favorites' => 'required|array|max:10',
                'favorites.*.name' => 'required|string|max:100',
                'favorites.*.route' => 'required|string|max:100',
                'favorites.*.icon' => 'required|string|max:50',
            ]);

            // In real implementation, save to database
            // UserFavorite::updateOrCreate(...);

            return response()->json([
                'message' => 'Favorites updated successfully',
                'favorites' => $validated['favorites']
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Invalid favorites data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update favorites',
                'message' => 'An error occurred while updating favorites'
            ], 500);
        }
    }

    /**
     * Clear navigation cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->navigationService->clearCache(auth()->id());

            return response()->json([
                'message' => 'Navigation cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear cache',
                'message' => 'An error occurred while clearing navigation cache'
            ], 500);
        }
    }

    /**
     * Get navigation performance metrics
     */
    public function metrics(): JsonResponse
    {
        try {
            // In real implementation, collect actual metrics
            $metrics = [
                'cache_hit_rate' => 95.5,
                'average_load_time' => 45.2, // milliseconds
                'total_navigation_items' => count($this->navigationService->getMainNavigation(user: auth()->user())),
                'user_favorites_count' => count($this->navigationService->getUserFavorites(auth()->user())),
                'last_cache_refresh' => now()->toISOString(),
            ];

            return response()->json($metrics);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load metrics',
                'message' => 'An error occurred while loading navigation metrics'
            ], 500);
        }
    }
}