<?php

namespace App\Console\Commands;

use App\Services\OdooService;
use App\Services\OdooSyncService;
use App\Services\CustomerSyncService;
use App\Services\JobSyncService;
use Illuminate\Console\Command;

class OdooSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'odoo:sync
                            {--type=all : Type of sync (all, products, stock, push, pull, customers, jobs, status)}
                            {--strategy=newest_wins : Conflict strategy (odoo_wins, local_wins, newest_wins)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize data between Laravel and Odoo';

    /**
     * Execute the console command.
     */
    public function handle(OdooService $odoo): int
    {
        $type = $this->option('type');
        $strategy = $this->option('strategy');

        $this->info('Starting Odoo sync...');
        $this->newLine();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Sync Type', $type],
                ['Conflict Strategy', $strategy],
            ]
        );

        $this->newLine();

        try {
            $productSyncService = new OdooSyncService($odoo);
            $productSyncService->setConflictStrategy($strategy);

            $customerSyncService = new CustomerSyncService($odoo);
            $customerSyncService->setConflictStrategy($strategy);

            $jobSyncService = new JobSyncService($odoo);

            $stats = match ($type) {
                'products' => $this->syncProducts($productSyncService),
                'stock' => $this->syncStock($productSyncService),
                'push' => $this->pushToOdoo($productSyncService),
                'pull' => $this->pullFromOdoo($productSyncService),
                'customers' => $this->syncCustomers($customerSyncService),
                'jobs' => $this->syncJobs($jobSyncService),
                'status' => $this->showStatus($productSyncService, $customerSyncService, $jobSyncService),
                default => $this->syncAll($productSyncService, $customerSyncService, $jobSyncService),
            };

            $this->newLine();
            $this->info('Sync completed successfully!');

            if (isset($stats) && is_array($stats)) {
                $this->newLine();
                $this->table(
                    ['Metric', 'Count'],
                    collect($stats)->map(fn($v, $k) => [ucfirst(str_replace('_', ' ', $k)), $v])->values()->toArray()
                );
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Full product sync (bidirectional)
     */
    protected function syncProducts(OdooSyncService $syncService): array
    {
        $this->info('Running full product sync...');
        return $syncService->syncProducts();
    }

    /**
     * Stock levels sync only
     */
    protected function syncStock(OdooSyncService $syncService): array
    {
        $this->info('Syncing stock levels from Odoo...');
        return $syncService->syncStockLevels();
    }

    /**
     * Push local products to Odoo
     */
    protected function pushToOdoo(OdooSyncService $syncService): array
    {
        $this->info('Pushing local products to Odoo...');
        return $syncService->pushProductsToOdoo();
    }

    /**
     * Pull products from Odoo
     */
    protected function pullFromOdoo(OdooSyncService $syncService): array
    {
        $this->info('Pulling products from Odoo...');
        return $syncService->pullProductsFromOdoo();
    }

    /**
     * Sync customers bidirectionally
     */
    protected function syncCustomers(CustomerSyncService $syncService): array
    {
        $this->info('Syncing customers with Odoo...');
        return $syncService->syncCustomers();
    }

    /**
     * Sync job postings from Odoo
     */
    protected function syncJobs(JobSyncService $syncService): array
    {
        $this->info('Syncing job postings from Odoo...');
        return $syncService->syncJobPostings();
    }

    /**
     * Show sync status
     */
    protected function showStatus(OdooSyncService $productSync, CustomerSyncService $customerSync, JobSyncService $jobSync): array
    {
        $this->info('Current Sync Status:');
        $this->newLine();

        $productStatus = $productSync->getSyncStatus();
        $customerStatus = $customerSync->getSyncStatus();

        $this->info('Products:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products', $productStatus['total_products']],
                ['Synced with Odoo', $productStatus['synced_products']],
                ['Not Synced', $productStatus['unsynced_products']],
                ['Last Sync', $productStatus['last_sync'] ?? 'Never'],
                ['Sync Logs Today', $productStatus['sync_logs_today']],
                ['Errors Today', $productStatus['sync_errors_today']],
            ]
        );

        $this->newLine();
        $this->info('Customers:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Customers', $customerStatus['total_customers']],
                ['Synced with Odoo', $customerStatus['synced_customers']],
                ['Not Synced', $customerStatus['unsynced_customers']],
                ['From Inquiries', $customerStatus['from_inquiries']],
                ['From Odoo', $customerStatus['from_odoo']],
                ['Last Sync', $customerStatus['last_sync'] ?? 'Never'],
            ]
        );

        $jobStatus = $jobSync->getSyncStatus();

        $this->newLine();
        $this->info('Job Postings:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Jobs', $jobStatus['total_jobs']],
                ['Active Jobs', $jobStatus['active_jobs']],
                ['Synced with Odoo', $jobStatus['synced_jobs']],
                ['Last Sync', $jobStatus['last_sync'] ?? 'Never'],
            ]
        );

        return array_merge($productStatus, $customerStatus, $jobStatus);
    }

    /**
     * Full sync (all data types)
     */
    protected function syncAll(OdooSyncService $productSync, CustomerSyncService $customerSync, JobSyncService $jobSync): array
    {
        $this->info('Running full bidirectional sync...');
        $this->newLine();

        $allStats = [];

        // Sync products
        $this->info('Step 1: Syncing products...');
        $productStats = $productSync->syncProducts();
        $allStats['products_pulled'] = $productStats['pulled'];
        $allStats['products_pushed'] = $productStats['pushed'];
        $allStats['products_conflicts'] = $productStats['conflicts'];

        $this->newLine();

        // Sync stock
        $this->info('Step 2: Syncing stock levels...');
        $stockStats = $productSync->syncStockLevels();
        $allStats['stock_updated'] = $stockStats['updated'];

        $this->newLine();

        // Sync customers
        $this->info('Step 3: Syncing customers...');
        $customerStats = $customerSync->syncCustomers();
        $allStats['customers_pulled'] = $customerStats['pulled'];
        $allStats['customers_pushed'] = $customerStats['pushed'];

        $this->newLine();

        // Sync job postings
        $this->info('Step 4: Syncing job postings...');
        $jobStats = $jobSync->syncJobPostings();
        $allStats['jobs_synced'] = $jobStats['synced'];
        $allStats['jobs_created'] = $jobStats['created'];
        $allStats['jobs_updated'] = $jobStats['updated'];

        return $allStats;
    }
}
