<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with hierarchical structure.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Get all top-level categories (those without parents) with their children
        $categories = Category::with(['children' => function ($query) {
            $query->orderBy('order', 'asc');
        }])
            ->whereNull('parent_id')
            ->orderBy('order', 'asc')
            ->get();

        return response()->json([
            'data' => $categories
        ], 200);
    }

    /**
     * Display the specified category by slug with its products.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $category = Category::with([
            'parent:id,name,slug,description',
            'children' => function ($query) {
                $query->orderBy('order', 'asc')
                    ->select('id', 'name', 'slug', 'description', 'parent_id', 'image', 'order');
            },
            'products' => function ($query) {
                $query->select('id', 'title', 'slug', 'description', 'price_zwl', 'price_usd',
                    'category_id', 'cover_image', 'stock_status', 'stock_quantity', 'featured')
                    ->orderBy('featured', 'desc')
                    ->orderBy('created_at', 'desc')
                    ->limit(20);
            }
        ])
            ->where('slug', $slug)
            ->first();

        if (!$category) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'data' => $category
        ], 200);
    }
}
