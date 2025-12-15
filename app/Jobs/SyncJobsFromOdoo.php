<?php

namespace App\Jobs;

use App\Services\JobSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncJobsFromOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct()
    {
        //
    }

    public function handle(JobSyncService $jobSyncService): void
    {
        try {
            Log::info('Starting job postings sync from Odoo');

            $stats = $jobSyncService->syncJobPostings();

            Log::info('Job postings sync completed successfully', $stats);

        } catch (\Exception $e) {
            Log::error('Job postings sync failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }
}
