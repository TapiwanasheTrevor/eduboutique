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
                  ->orWhere('subject', 'like', "%{$searchTerm}%");
            });
        }

        // Filters
        if ($request->has('level')) {
            $query->whereIn('level', (array) $request->level);
        }

        if ($request->has('subject')) {
            $query->whereIn('subject', (array) $request->subject);
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
            'levels' => Video::select('level')->distinct()->whereNotNull('level')->where('published', true)->pluck('level')->toArray(),
            'subjects' => Video::select('subject')->distinct()->whereNotNull('subject')->where('published', true)->pluck('subject')->toArray(),
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
                'level' => $request->level,
                'subject' => $request->subject,
            ],
        ]);
    }
}
