<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NavigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private NavigationService $navigationService
    ) {}

    /**
     * Get navigation structure for authenticated user
     */
    public function index(): JsonResponse
    {
        $navigation = $this->navigationService->getNavigation();

        return response()->json([
            'status' => 'success',
            'data' => [
                'navigation' => $navigation,
            ],
        ]);
    }

    /**
     * Get mobile navigation structure
     */
    public function mobile(): JsonResponse
    {
        $navigation = $this->navigationService->getMobileNavigation();

        return response()->json([
            'status' => 'success',
            'data' => [
                'navigation' => $navigation,
            ],
        ]);
    }

    /**
     * Search navigation items
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:50',
        ]);

        $query = $request->input('query');
        $navigation = $this->navigationService->getNavigation();

        // Simple search implementation
        $results = $this->searchInNavigation($navigation, $query);

        return response()->json([
            'status' => 'success',
            'data' => [
                'query' => $query,
                'results' => $results,
            ],
        ]);
    }

    /**
     * Clear navigation cache
     */
    public function clearCache(): JsonResponse
    {
        $this->navigationService->clearCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Navigation cache cleared successfully',
        ]);
    }

    /**
     * Search through navigation array
     */
    private function searchInNavigation(array $navigation, string $query): array
    {
        $results = [];
        $query = strtolower($query);

        foreach ($navigation as $item) {
            // Search in item name
            if (str_contains(strtolower($item['name']), $query)) {
                $results[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'icon' => $item['icon'],
                    'route' => $item['route'] ?? null,
                    'type' => $item['type'],
                ];
            }

            // Search in children if it's a section
            if ($item['type'] === 'section' && isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (str_contains(strtolower($child['name']), $query)) {
                        $results[] = [
                            'id' => $child['id'],
                            'name' => $child['name'],
                            'icon' => $child['icon'],
                            'route' => $child['route'] ?? null,
                            'type' => $child['type'],
                            'parent' => $item['name'],
                        ];
                    }
                }
            }
        }

        return $results;
    }
}
