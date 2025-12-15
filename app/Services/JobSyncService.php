<?php

namespace App\Services;

use App\Models\JobPosting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class JobSyncService
{
    protected OdooService $odoo;

    protected array $jobSyncFields = [
        'name',
        'description',
        'requirements',
        'department_id',
        'address_id',
        'contract_type_id',
        'expected_employees',
        'no_of_recruitment',
        'is_published',
        'write_date',
        'create_date',
    ];

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    public function syncJobPostings(): array
    {
        $stats = [
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
            'errors' => 0,
        ];

        DB::beginTransaction();

        try {
            $stats = $this->pullJobsFromOdoo();
            DB::commit();

            Log::info('Job postings sync completed', $stats);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Job postings sync failed: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    public function pullJobsFromOdoo(): array
    {
        $stats = ['synced' => 0, 'created' => 0, 'updated' => 0, 'deactivated' => 0, 'errors' => 0];

        Log::info('Pulling job postings from Odoo...');

        try {
            // Get ALL job positions from Odoo (respect is_published as active status)
            // hr.job model stores job positions
            $odooJobs = $this->odoo->search(
                'hr.job',
                [], // No filter - get all jobs
                $this->jobSyncFields
            );

            Log::info('Found ' . count($odooJobs) . ' jobs in Odoo');

            $syncedOdooIds = [];

            foreach ($odooJobs as $odooJob) {
                try {
                    $result = $this->syncJobFromOdoo($odooJob);
                    $syncedOdooIds[] = $odooJob['id'];

                    if ($result === 'created') {
                        $stats['created']++;
                    } elseif ($result === 'updated') {
                        $stats['updated']++;
                    }
                    $stats['synced']++;
                } catch (\Exception $e) {
                    Log::error('Failed to sync job: ' . ($odooJob['name'] ?? 'unknown'), [
                        'error' => $e->getMessage()
                    ]);
                    $stats['errors']++;
                }
            }

            // Deactivate jobs that no longer exist in Odoo
            $deactivated = JobPosting::whereNotNull('odoo_job_id')
                ->whereNotIn('odoo_job_id', $syncedOdooIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $stats['deactivated'] = $deactivated;

        } catch (\Exception $e) {
            Log::error('Failed to pull jobs from Odoo: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    protected function syncJobFromOdoo(array $odooJob): string
    {
        $odooId = $odooJob['id'];

        // Check if job exists locally
        $localJob = JobPosting::where('odoo_job_id', $odooId)->first();

        if ($localJob) {
            $this->updateJobFromOdoo($localJob, $odooJob);
            return 'updated';
        }

        // Create new job posting
        $this->createJobFromOdoo($odooJob);
        return 'created';
    }

    protected function createJobFromOdoo(array $odooJob): JobPosting
    {
        $title = $odooJob['name'];
        $department = $this->extractDepartmentName($odooJob['department_id'] ?? false);
        $location = $this->extractLocationName($odooJob['address_id'] ?? false);
        $employmentType = $this->mapContractType($odooJob['contract_type_id'] ?? false);

        $job = JobPosting::create([
            'odoo_job_id' => $odooJob['id'],
            'title' => $title,
            'slug' => $this->generateUniqueSlug($title),
            'description' => $this->cleanHtml($odooJob['description'] ?? ''),
            'requirements' => $this->cleanHtml($odooJob['requirements'] ?? ''),
            'department' => $department,
            'location' => $location ?: 'Harare, Zimbabwe',
            'employment_type' => $employmentType,
            'is_active' => $odooJob['is_published'] ?? false, // Respect Odoo's published status
            'published_at' => ($odooJob['is_published'] ?? false)
                ? (isset($odooJob['create_date']) ? Carbon::parse($odooJob['create_date']) : now())
                : null,
            'odoo_synced_at' => now(),
        ]);

        Log::info('Created job posting from Odoo: ' . $job->title);

        return $job;
    }

    protected function updateJobFromOdoo(JobPosting $job, array $odooJob): void
    {
        $department = $this->extractDepartmentName($odooJob['department_id'] ?? false);
        $location = $this->extractLocationName($odooJob['address_id'] ?? false);
        $employmentType = $this->mapContractType($odooJob['contract_type_id'] ?? false);

        $isPublished = $odooJob['is_published'] ?? false;

        $job->update([
            'title' => $odooJob['name'],
            'description' => $this->cleanHtml($odooJob['description'] ?? $job->description),
            'requirements' => $this->cleanHtml($odooJob['requirements'] ?? $job->requirements),
            'department' => $department ?: $job->department,
            'location' => $location ?: $job->location,
            'employment_type' => $employmentType ?: $job->employment_type,
            'is_active' => $isPublished,
            'published_at' => $isPublished && !$job->published_at ? now() : $job->published_at,
            'odoo_synced_at' => now(),
        ]);

        Log::info('Updated job posting from Odoo: ' . $job->title);
    }

    protected function extractDepartmentName($departmentId): ?string
    {
        if (!$departmentId || $departmentId === false) {
            return null;
        }

        // In Odoo, many2one fields return [id, name] array
        if (is_array($departmentId)) {
            return $departmentId[1] ?? null;
        }

        return null;
    }

    protected function extractLocationName($addressId): ?string
    {
        if (!$addressId || $addressId === false) {
            return null;
        }

        if (is_array($addressId)) {
            return $addressId[1] ?? null;
        }

        return null;
    }

    protected function mapContractType($contractTypeId): string
    {
        if (!$contractTypeId || $contractTypeId === false) {
            return 'full_time';
        }

        // In Odoo, contract_type_id is a many2one to hr.contract.type
        $typeName = is_array($contractTypeId) ? strtolower($contractTypeId[1] ?? '') : '';

        return match (true) {
            str_contains($typeName, 'part') => 'part_time',
            str_contains($typeName, 'contract') => 'contract',
            str_contains($typeName, 'intern') => 'internship',
            str_contains($typeName, 'temp') => 'contract',
            default => 'full_time',
        };
    }

    protected function cleanHtml(?string $html): ?string
    {
        if (empty($html)) {
            return null;
        }

        // Keep the HTML but sanitize dangerous tags
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        return trim($html);
    }

    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (JobPosting::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    public function getSyncStatus(): array
    {
        return [
            'total_jobs' => JobPosting::count(),
            'active_jobs' => JobPosting::where('is_active', true)->count(),
            'synced_jobs' => JobPosting::whereNotNull('odoo_job_id')->count(),
            'last_sync' => JobPosting::whereNotNull('odoo_synced_at')
                ->orderBy('odoo_synced_at', 'desc')
                ->value('odoo_synced_at'),
        ];
    }
}
