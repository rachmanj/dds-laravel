<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MenuSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MenuSearchController extends Controller
{
    public function index(Request $request, MenuSearchService $menuSearchService): JsonResponse
    {
        $user = $request->user();
        $permissions = $user->getAllPermissions()->pluck('name')->sort()->values()->all();
        $fingerprint = md5(implode('|', $permissions));
        $cacheKey = "menu_items_user_{$user->id}_{$fingerprint}";

        $items = Cache::remember($cacheKey, 3600, fn () => $menuSearchService->buildForUser($user));

        $q = mb_strtolower(trim((string) $request->query('q', '')));
        if ($q !== '') {
            $items = array_values(array_filter(
                $items,
                static fn (array $item): bool => str_contains((string) $item['searchText'], $q)
            ));
            $items = array_slice($items, 0, 15);
        }

        return response()->json(['items' => $items]);
    }
}
