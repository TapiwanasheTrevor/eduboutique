<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\OdooService;
use App\Services\CustomerSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCustomerToOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Customer $customer
    ) {}

    public function handle(): void
    {
        // Skip if already synced
        if ($this->customer->odoo_partner_id) {
            Log::info('Customer already synced to Odoo', [
                'customer' => $this->customer->email,
                'odoo_partner_id' => $this->customer->odoo_partner_id,
            ]);
            return;
        }

        try {
            $odoo = app(OdooService::class);
            $syncService = new CustomerSyncService($odoo);

            $odooId = $syncService->pushCustomerToOdoo($this->customer);

            Log::info('Customer synced to Odoo successfully', [
                'customer' => $this->customer->email,
                'odoo_partner_id' => $odooId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync customer to Odoo', [
                'customer' => $this->customer->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
