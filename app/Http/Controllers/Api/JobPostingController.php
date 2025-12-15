<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobPostingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobPosting::active()
            ->published()
            ->notExpired();

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        // Filter by employment type
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'LIKE', "%{$request->input('location')}%");
        }

        // Search by title or description
        if ($request->filled('query')) {
            $searchTerm = $request->input('query');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('department', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            case 'deadline':
                $query->orderBy('deadline', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('published_at', 'desc');
                break;
        }

        // Pagination
        $perPage = min($request->input('per_page', 10), 50);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'from' => $jobs->firstItem(),
                'to' => $jobs->lastItem(),
            ]
        ], 200);
    }

    public function show(string $slug): JsonResponse
    {
        $job = JobPosting::active()
            ->published()
            ->notExpired()
            ->where('slug', $slug)
            ->first();

        if (!$job) {
            return response()->json([
                'message' => 'Job posting not found'
            ], 404);
        }

        return response()->json([
            'data' => $job
        ], 200);
    }

    public function departments(): JsonResponse
    {
        $departments = JobPosting::active()
            ->published()
            ->notExpired()
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        return response()->json([
            'data' => $departments
        ], 200);
    }
}
