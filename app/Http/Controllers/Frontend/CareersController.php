<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CareersController extends Controller
{
    public function index(Request $request)
    {
        $query = JobPosting::active()
            ->published()
            ->notExpired();

        // Search
        if ($request->has('q') && $request->q) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('department', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by department
        if ($request->has('department') && $request->department) {
            $query->whereIn('department', (array) $request->department);
        }

        // Filter by employment type
        if ($request->has('employment_type') && $request->employment_type) {
            $query->whereIn('employment_type', (array) $request->employment_type);
        }

        // Filter by location
        if ($request->has('location') && $request->location) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('published_at', 'asc');
                break;
            case 'deadline':
                $query->orderByRaw('COALESCE(deadline, \'9999-12-31\') ASC');
                break;
            default: // newest
                $query->orderBy('published_at', 'desc');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $jobs = $query->paginate($perPage);

        // Get unique filter values
        $filterOptions = [
            'departments' => JobPosting::active()
                ->published()
                ->notExpired()
                ->whereNotNull('department')
                ->distinct()
                ->pluck('department')
                ->toArray(),
            'employment_types' => [
                'full_time' => 'Full Time',
                'part_time' => 'Part Time',
                'contract' => 'Contract',
                'internship' => 'Internship',
            ],
        ];

        return Inertia::render('careers/CareersPage', [
            'jobs' => $jobs->items(),
            'total' => $jobs->total(),
            'perPage' => $jobs->perPage(),
            'currentPage' => $jobs->currentPage(),
            'lastPage' => $jobs->lastPage(),
            'filterOptions' => $filterOptions,
            'filters' => [
                'q' => $request->q,
                'sort' => $sort,
                'department' => $request->department,
                'employment_type' => $request->employment_type,
                'location' => $request->location,
            ],
        ]);
    }

    public function show(string $slug)
    {
        $job = JobPosting::active()
            ->published()
            ->notExpired()
            ->where('slug', $slug)
            ->firstOrFail();

        // Get related jobs from same department
        $relatedJobs = JobPosting::active()
            ->published()
            ->notExpired()
            ->where('id', '!=', $job->id)
            ->when($job->department, function ($query) use ($job) {
                return $query->where('department', $job->department);
            })
            ->limit(3)
            ->get();

        return Inertia::render('careers/JobDetailPage', [
            'job' => $job,
            'relatedJobs' => $relatedJobs,
        ]);
    }
}
