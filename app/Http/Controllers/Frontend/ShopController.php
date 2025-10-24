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
        $query = Product::query()->where('stock_status', '!=', 'out_of_stock');

        // Search
        if ($request->has('q') && $request->q) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('subject', 'like', "%{$searchTerm}%");
            });
        }

        // Filters
        if ($request->has('syllabus')) {
            $query->whereIn('syllabus', (array) $request->syllabus);
        }

        if ($request->has('level')) {
            $query->whereIn('level', (array) $request->level);
        }

        if ($request->has('subject')) {
            $query->whereIn('subject', (array) $request->subject);
        }

        if ($request->has('price_min')) {
            $query->where('price_usd', '>=', $request->price_min);
        }

        if ($request->has('price_max')) {
            $query->where('price_usd', '<=', $request->price_max);
        }

        if ($request->has('stock_status')) {
            $query->whereIn('stock_status', (array) $request->stock_status);
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

        // Pagination
        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage);

        // Get unique filter values for sidebar
        $filterOptions = [
            'syllabuses' => Product::select('syllabus')->distinct()->whereNotNull('syllabus')->pluck('syllabus')->toArray(),
            'levels' => Product::select('level')->distinct()->whereNotNull('level')->pluck('level')->toArray(),
            'subjects' => Product::select('subject')->distinct()->whereNotNull('subject')->pluck('subject')->toArray(),
        ];

        return Inertia::render('shop/ShopPage', [
            'products' => $products->items(),
            'total' => $products->total(),
            'perPage' => $products->perPage(),
            'currentPage' => $products->currentPage(),
            'lastPage' => $products->lastPage(),
            'filterOptions' => $filterOptions,
            'filters' => [
                'q' => $request->q,
                'sort' => $sort,
                'syllabus' => $request->syllabus,
                'level' => $request->level,
                'subject' => $request->subject,
            ],
        ]);
    }
}
