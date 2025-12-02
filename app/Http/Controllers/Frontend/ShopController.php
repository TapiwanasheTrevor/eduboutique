<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildProductQuery($request);

        // Pagination - increased default for infinite scroll
        $perPage = $request->get('per_page', 24);
        $products = $query->paginate($perPage);

        // Get unique filter values for sidebar (only on initial page load)
        $filterOptions = $this->getFilterOptions();

        return Inertia::render('shop/ShopPage', [
            'products' => $products->items(),
            'total' => $products->total(),
            'perPage' => $products->perPage(),
            'currentPage' => $products->currentPage(),
            'lastPage' => $products->lastPage(),
            'hasMorePages' => $products->hasMorePages(),
            'filterOptions' => $filterOptions,
            'filters' => [
                'q' => $request->q,
                'sort' => $request->get('sort', 'featured'),
                'syllabus' => $request->syllabus,
                'level' => $request->level,
                'subject' => $request->subject,
                'author' => $request->author,
                'publisher' => $request->publisher,
            ],
        ]);
    }

    /**
     * API endpoint for infinite scroll - returns JSON
     */
    public function loadMore(Request $request)
    {
        $query = $this->buildProductQuery($request);

        $perPage = $request->get('per_page', 24);
        $products = $query->paginate($perPage);

        return response()->json([
            'products' => $products->items(),
            'currentPage' => $products->currentPage(),
            'lastPage' => $products->lastPage(),
            'hasMorePages' => $products->hasMorePages(),
            'total' => $products->total(),
        ]);
    }

    /**
     * Build the product query with all filters applied
     */
    private function buildProductQuery(Request $request)
    {
        $query = Product::query()->where('stock_status', '!=', 'out_of_stock');

        // Search
        if ($request->has('q') && $request->q) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('subject', 'like', "%{$searchTerm}%")
                    ->orWhere('author', 'like', "%{$searchTerm}%")
                    ->orWhere('publisher', 'like', "%{$searchTerm}%")
                    ->orWhere('level', 'like', "%{$searchTerm}%");
            });
        }

        // Filters - only apply if value is not empty
        if ($request->filled('syllabus')) {
            $syllabusValues = array_filter((array) $request->syllabus, fn($v) => $v !== '' && $v !== null);
            if (!empty($syllabusValues)) {
                $query->whereIn('syllabus', $syllabusValues);
            }
        }

        if ($request->filled('level')) {
            $levelValues = array_filter((array) $request->level, fn($v) => $v !== '' && $v !== null);
            if (!empty($levelValues)) {
                $query->whereIn('level', $levelValues);
            }
        }

        if ($request->filled('subject')) {
            $subjectValues = array_filter((array) $request->subject, fn($v) => $v !== '' && $v !== null);
            if (!empty($subjectValues)) {
                $query->whereIn('subject', $subjectValues);
            }
        }

        if ($request->filled('author')) {
            $authorValues = array_filter((array) $request->author, fn($v) => $v !== '' && $v !== null);
            if (!empty($authorValues)) {
                $query->whereIn('author', $authorValues);
            }
        }

        if ($request->filled('publisher')) {
            $publisherValues = array_filter((array) $request->publisher, fn($v) => $v !== '' && $v !== null);
            if (!empty($publisherValues)) {
                $query->whereIn('publisher', $publisherValues);
            }
        }

        if ($request->filled('price_min')) {
            $query->where('price_usd', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price_usd', '<=', $request->price_max);
        }

        if ($request->filled('stock_status')) {
            $stockValues = array_filter((array) $request->stock_status, fn($v) => $v !== '' && $v !== null);
            if (!empty($stockValues)) {
                $query->whereIn('stock_status', $stockValues);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'featured');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_usd', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_usd', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'alphabetical':
                $query->orderBy('title', 'asc');
                break;
            default: // featured
                $query->orderBy('featured', 'desc')
                    ->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Get unique filter options for the sidebar
     */
    private function getFilterOptions(): array
    {
        return [
            'syllabuses' => Product::select('syllabus')->distinct()->whereNotNull('syllabus')->where('syllabus', '!=', '')->orderBy('syllabus')->pluck('syllabus')->toArray(),
            'levels' => Product::select('level')->distinct()->whereNotNull('level')->where('level', '!=', '')->orderBy('level')->pluck('level')->toArray(),
            'subjects' => Product::select('subject')->distinct()->whereNotNull('subject')->where('subject', '!=', '')->orderBy('subject')->pluck('subject')->toArray(),
            'authors' => Product::select('author')->distinct()->whereNotNull('author')->where('author', '!=', '')->where('author', '!=', 'Unknown')->orderBy('author')->pluck('author')->toArray(),
            'publishers' => Product::select('publisher')->distinct()->whereNotNull('publisher')->where('publisher', '!=', '')->where('publisher', '!=', 'Unknown')->orderBy('publisher')->pluck('publisher')->toArray(),
        ];
    }
}
