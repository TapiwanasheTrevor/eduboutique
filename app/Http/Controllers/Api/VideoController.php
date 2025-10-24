<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VideoController extends Controller
{
    /**
     * Display a listing of videos with optional category filter.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Video::where('published', true);

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $videos = $query->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $videos
        ], 200);
    }

    /**
     * Display the specified video.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $video = Video::where('published', true)
            ->find($id);

        if (!$video) {
            return response()->json([
                'message' => 'Video not found'
            ], 404);
        }

        // Increment views count
        $video->increment('views');

        return response()->json([
            'data' => $video
        ], 200);
    }
}
