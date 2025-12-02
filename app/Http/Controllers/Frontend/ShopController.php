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
                // Match subjects case-insensitively, also matching variations:
                // "and" with "&", "and" with ", " (comma)
                $query->where(function ($q) use ($subjectValues) {
                    foreach ($subjectValues as $subject) {
                        $lowerSubject = strtolower($subject);
                        $q->orWhereRaw('LOWER(subject) = ?', [$lowerSubject]);

                        // Variant: "and" -> "&"
                        $ampersandVariant = str_replace(' and ', ' & ', $lowerSubject);
                        if ($ampersandVariant !== $lowerSubject) {
                            $q->orWhereRaw('LOWER(subject) = ?', [$ampersandVariant]);
                        }

                        // Variant: "and" -> ", " (for two-part subjects)
                        $commaVariant = str_replace(' and ', ', ', $lowerSubject);
                        if ($commaVariant !== $lowerSubject) {
                            $q->orWhereRaw('LOWER(subject) = ?', [$commaVariant]);
                        }
                    }
                });
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
     * Normalize a subject string for consistent display and deduplication
     * - Converts & to "and"
     * - Converts ", " (comma followed by space and no "and") to " and "
     * - Normalizes whitespace
     * - Converts to title case
     */
    private function normalizeSubject(string $subject): string
    {
        // Replace & with "and"
        $normalized = str_replace('&', 'and', $subject);

        // Normalize multiple spaces to single space
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Replace ", " with " and " when it separates two topics (not a list with "and")
        // Only if the subject doesn't already contain " and "
        $parts = explode(', ', $normalized);
        if (count($parts) === 2 && stripos($normalized, ' and ') === false) {
            $normalized = implode(' and ', $parts);
        }

        // Trim and convert to lowercase for consistent processing
        $normalized = strtolower(trim($normalized));

        // Convert to title case (capitalize first letter of each word)
        $normalized = ucwords($normalized);

        return $normalized;
    }

    /**
     * Get unique filter options for the sidebar
     */
    private function getFilterOptions(): array
    {
        // Get subjects and normalize, deduplicating after normalization
        $rawSubjects = Product::select('subject')->distinct()->whereNotNull('subject')->where('subject', '!=', '')->pluck('subject')->toArray();
        $normalizedSubjects = [];
        $seen = [];
        foreach ($rawSubjects as $subject) {
            $normalized = $this->normalizeSubject($subject);
            $key = strtolower($normalized);
            if (!isset($seen[$key]) && $normalized !== 'Unknown') {
                $normalizedSubjects[] = $normalized;
                $seen[$key] = true;
            }
        }
        sort($normalizedSubjects);

        return [
            'syllabuses' => Product::select('syllabus')->distinct()->whereNotNull('syllabus')->where('syllabus', '!=', '')->orderBy('syllabus')->pluck('syllabus')->toArray(),
            'levels' => Product::select('level')->distinct()->whereNotNull('level')->where('level', '!=', '')->orderBy('level')->pluck('level')->toArray(),
            'subjects' => $normalizedSubjects,
            'authors' => Product::select('author')->distinct()->whereNotNull('author')->where('author', '!=', '')->where('author', '!=', 'Unknown')->orderBy('author')->pluck('author')->toArray(),
            'publishers' => Product::select('publisher')->distinct()->whereNotNull('publisher')->where('publisher', '!=', '')->where('publisher', '!=', 'Unknown')->orderBy('publisher')->pluck('publisher')->toArray(),
        ];
    }
}
