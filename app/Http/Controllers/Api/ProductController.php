<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display a paginated listing of products with filters and search.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category:id,name,slug');

        // Search by query term
        if ($request->filled('query')) {
            $searchTerm = $request->input('query');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('author', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('publisher', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('isbn', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by syllabus
        if ($request->filled('syllabus')) {
            $syllabusFilters = is_array($request->input('syllabus'))
                ? $request->input('syllabus')
                : [$request->input('syllabus')];
            $query->whereIn('syllabus', $syllabusFilters);
        }

        // Filter by level
        if ($request->filled('level')) {
            $levelFilters = is_array($request->input('level'))
                ? $request->input('level')
                : [$request->input('level')];
            $query->whereIn('level', $levelFilters);
        }

        // Filter by subject
        if ($request->filled('subject')) {
            $subjectFilters = is_array($request->input('subject'))
                ? $request->input('subject')
                : [$request->input('subject')];
            $query->whereIn('subject', $subjectFilters);
        }

        // Filter by price range
        if ($request->filled('price_min')) {
            $query->where('price_usd', '>=', $request->input('price_min'));
        }

        if ($request->filled('price_max')) {
            $query->where('price_usd', '<=', $request->input('price_max'));
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $stockFilters = is_array($request->input('stock_status'))
                ? $request->input('stock_status')
                : [$request->input('stock_status')];
            $query->whereIn('stock_status', $stockFilters);
        }

        // Sorting
        $sort = $request->input('sort', 'featured');
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('price_usd', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_usd', 'desc');
                break;
            case 'alphabetical':
                $query->orderBy('title', 'asc');
                break;
            case 'featured':
            default:
                $query->orderBy('featured', 'desc')
                    ->orderBy('created_at', 'desc');
                break;
        }

        // Pagination
        $perPage = min($request->input('per_page', 12), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ]
        ], 200);
    }

    /**
     * Display the specified product by slug.
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::with('category:id,name,slug,description')
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'data' => $product
        ], 200);
    }

    /**
     * Get featured products.
     *
     * @return JsonResponse
     */
    public function featured(): JsonResponse
    {
        $products = Product::with('category:id,name,slug')
            ->where('featured', true)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        return response()->json([
            'data' => $products
        ], 200);
    }
}
