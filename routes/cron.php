<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\VerifyCpanelSecret;

/**
 * Cron Job Routes for cPanel
 *
 * These routes can be called by cPanel cron jobs to run scheduled tasks.
 *
 * Example cron job setup in cPanel:
 * Every minute: /usr/local/bin/php /home/user/public_html/artisan schedule:run
 * Or via URL: wget -q -O /dev/null "https://yourdomain.com/cron/scheduler?key=YOUR_SECRET"
 *
 * For queue processing (every minute):
 * wget -q -O /dev/null "https://yourdomain.com/cron/queue?key=YOUR_SECRET"
 *
 * For Odoo sync (every hour):
 * wget -q -O /dev/null "https://yourdomain.com/cron/odoo-sync?key=YOUR_SECRET"
 */

Route::middleware(VerifyCpanelSecret::class)->prefix('cron')->group(function () {

    // Run Laravel scheduler
    Route::get('/scheduler', function () {
        try {
            Artisan::call('schedule:run');
            $output = Artisan::output();

            Log::info('Scheduler run via cron', ['output' => $output]);

            return response('OK: ' . $output, 200);
        } catch (\Exception $e) {
            Log::error('Scheduler cron failed', ['error' => $e->getMessage()]);
            return response('Error: ' . $e->getMessage(), 500);
        }
    });

    // Process queue jobs
    Route::get('/queue', function () {
        try {
            // Process jobs for up to 55 seconds (leave buffer for cron)
            $startTime = time();
            $processed = 0;

            while (time() - $startTime < 55) {
                Artisan::call('queue:work', [
                    '--once' => true,
                    '--tries' => 3,
                    '--timeout' => 30,
                ]);

                $output = Artisan::output();

                // If no jobs were processed, break
                if (str_contains($output, 'No jobs')) {
                    break;
                }

                $processed++;

                // Safety limit
                if ($processed >= 20) {
                    break;
                }
            }

            Log::info('Queue processed via cron', ['jobs' => $processed]);

            return response("OK: Processed {$processed} jobs", 200);
        } catch (\Exception $e) {
            Log::error('Queue cron failed', ['error' => $e->getMessage()]);
            return response('Error: ' . $e->getMessage(), 500);
        }
    });

    // Odoo product sync (run hourly)
    Route::get('/odoo-sync', function () {
        try {
            Artisan::call('odoo:sync', ['--type' => 'stock']);
            $output = Artisan::output();

            Log::info('Odoo sync via cron', ['output' => $output]);

            return response('OK: ' . $output, 200);
        } catch (\Exception $e) {
            Log::error('Odoo sync cron failed', ['error' => $e->getMessage()]);
            return response('Error: ' . $e->getMessage(), 500);
        }
    });

    // Full Odoo sync (run daily)
    Route::get('/odoo-full-sync', function () {
        try {
            Artisan::call('odoo:sync', ['--type' => 'all']);
            $output = Artisan::output();

            Log::info('Odoo full sync via cron', ['output' => $output]);

            return response('OK: ' . $output, 200);
        } catch (\Exception $e) {
            Log::error('Odoo full sync cron failed', ['error' => $e->getMessage()]);
            return response('Error: ' . $e->getMessage(), 500);
        }
    });

    // Clear expired cache (run daily)
    Route::get('/cleanup', function () {
        try {
            // Clear expired cache
            Artisan::call('cache:prune-stale-tags');

            // Clear old logs (keep last 7 days)
            $logPath = storage_path('logs');
            $files = glob($logPath . '/laravel-*.log');
            $cutoff = strtotime('-7 days');

            $deleted = 0;
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                    $deleted++;
                }
            }

            Log::info('Cleanup via cron', ['deleted_logs' => $deleted]);

            return response("OK: Deleted {$deleted} old log files", 200);
        } catch (\Exception $e) {
            Log::error('Cleanup cron failed', ['error' => $e->getMessage()]);
            return response('Error: ' . $e->getMessage(), 500);
        }
    });
});
