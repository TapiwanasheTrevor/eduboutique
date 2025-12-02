<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VideosController extends Controller
{
    public function index(Request $request)
    {
        $query = Video::where('published', true);

        // Search
        if ($request->has('q') && $request->q) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('category', 'like', "%{$searchTerm}%");
            });
        }

        // Filters
        if ($request->has('category')) {
            $query->whereIn('category', (array) $request->category);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'alphabetical':
                $query->orderBy('title', 'asc');
                break;
            default: // newest
                $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 12);
        $videos = $query->paginate($perPage);

        // Get unique filter values
        $filterOptions = [
            'categories' => Video::select('category')->distinct()->whereNotNull('category')->where('published', true)->pluck('category')->toArray(),
        ];

        return Inertia::render('videos/VideosPage', [
            'videos' => $videos->items(),
            'total' => $videos->total(),
            'perPage' => $videos->perPage(),
            'currentPage' => $videos->currentPage(),
            'lastPage' => $videos->lastPage(),
            'filterOptions' => $filterOptions,
            'filters' => [
                'q' => $request->q,
                'sort' => $sort,
                'category' => $request->category,
            ],
        ]);
    }
}
