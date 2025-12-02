<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\OdooSyncLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerSyncService
{
    protected OdooService $odoo;

    protected string $conflictStrategy = 'newest_wins';

    protected array $customerSyncFields = [
        'name',
        'email',
        'phone',
        'mobile',
        'street',
        'street2',
        'city',
        'state_id',
        'zip',
        'country_id',
        'company_type',
        'write_date',
    ];

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    public function setConflictStrategy(string $strategy): self
    {
        $this->conflictStrategy = $strategy;
        return $this;
    }

    /**
     * Full bidirectional sync of customers
     */
    public function syncCustomers(): array
    {
        $stats = [
            'pulled' => 0,
            'pushed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        DB::beginTransaction();

        try {
            $pullStats = $this->pullCustomersFromOdoo();
            $stats['pulled'] = $pullStats['synced'];
            $stats['skipped'] += $pullStats['skipped'];

            $pushStats = $this->pushCustomersToOdoo();
            $stats['pushed'] = $pushStats['synced'];
            $stats['errors'] += $pushStats['errors'];

            DB::commit();

            Log::info('Customer sync completed', $stats);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Customer sync failed: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Pull customers from Odoo
     */
    public function pullCustomersFromOdoo(): array
    {
        $stats = ['synced' => 0, 'skipped' => 0];

        Log::info('Pulling customers from Odoo...');

        try {
            // Get customers from Odoo (res.partner with customer_rank > 0)
            $odooCustomers = $this->odoo->search(
                'res.partner',
                [['customer_rank', '>', 0], ['is_company', '=', false]],
                $this->customerSyncFields
            );

            Log::info('Found ' . count($odooCustomers) . ' customers in Odoo');

            foreach ($odooCustomers as $odooCustomer) {
                $result = $this->syncCustomerFromOdoo($odooCustomer);

                if ($result === 'synced') {
                    $stats['synced']++;
                } else {
                    $stats['skipped']++;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to pull customers from Odoo: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Push local customers to Odoo
     */
    public function pushCustomersToOdoo(): array
    {
        $stats = ['synced' => 0, 'skipped' => 0, 'errors' => 0];

        Log::info('Pushing local customers to Odoo...');

        $localOnlyCustomers = Customer::whereNull('odoo_partner_id')->get();

        Log::info('Found ' . $localOnlyCustomers->count() . ' local-only customers');

        foreach ($localOnlyCustomers as $customer) {
            try {
                $this->pushCustomerToOdoo($customer);
                $stats['synced']++;
            } catch (\Exception $e) {
                Log::error('Failed to push customer: ' . $customer->email, [
                    'error' => $e->getMessage()
                ]);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Sync a single customer from Odoo
     */
    protected function syncCustomerFromOdoo(array $odooCustomer): string
    {
        $odooId = $odooCustomer['id'];
        $email = $odooCustomer['email'] ?? null;

        if (!$email) {
            return 'skipped';
        }

        $localCustomer = Customer::where('odoo_partner_id', $odooId)
            ->orWhere('email', $email)
            ->first();

        if ($localCustomer) {
            $this->updateCustomerFromOdoo($localCustomer, $odooCustomer);
            return 'synced';
        }

        $this->createCustomerFromOdoo($odooCustomer);
        return 'synced';
    }

    /**
     * Create a customer from Odoo data
     */
    protected function createCustomerFromOdoo(array $odooCustomer): Customer
    {
        $customer = Customer::create([
            'odoo_partner_id' => $odooCustomer['id'],
            'name' => $odooCustomer['name'],
            'email' => $odooCustomer['email'],
            'phone' => $odooCustomer['phone'] ?? null,
            'mobile' => $odooCustomer['mobile'] ?? null,
            'street' => $odooCustomer['street'] ?? null,
            'street2' => $odooCustomer['street2'] ?? null,
            'city' => $odooCustomer['city'] ?? null,
            'zip' => $odooCustomer['zip'] ?? null,
            'type' => ($odooCustomer['company_type'] ?? 'person') === 'company' ? 'company' : 'individual',
            'source' => 'odoo',
            'odoo_synced_at' => now(),
        ]);

        Log::info('Created customer from Odoo: ' . $customer->email);

        return $customer;
    }

    /**
     * Update customer from Odoo data
     */
    protected function updateCustomerFromOdoo(Customer $customer, array $odooCustomer): void
    {
        $customer->update([
            'odoo_partner_id' => $odooCustomer['id'],
            'name' => $odooCustomer['name'],
            'phone' => $odooCustomer['phone'] ?? $customer->phone,
            'mobile' => $odooCustomer['mobile'] ?? $customer->mobile,
            'street' => $odooCustomer['street'] ?? $customer->street,
            'street2' => $odooCustomer['street2'] ?? $customer->street2,
            'city' => $odooCustomer['city'] ?? $customer->city,
            'zip' => $odooCustomer['zip'] ?? $customer->zip,
            'odoo_synced_at' => now(),
        ]);

        Log::info('Updated customer from Odoo: ' . $customer->email);
    }

    /**
     * Push a customer to Odoo
     */
    public function pushCustomerToOdoo(Customer $customer): int
    {
        // Check if customer exists in Odoo by email
        $existingPartners = $this->odoo->search(
            'res.partner',
            [['email', '=', $customer->email]],
            ['id', 'name', 'email']
        );

        if (!empty($existingPartners)) {
            // Link to existing
            $odooId = $existingPartners[0]['id'];
            $customer->update([
                'odoo_partner_id' => $odooId,
                'odoo_synced_at' => now(),
            ]);

            Log::info('Linked customer to existing Odoo partner', [
                'customer' => $customer->email,
                'odoo_id' => $odooId
            ]);

            return $odooId;
        }

        // Create new partner in Odoo
        $partnerData = [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'mobile' => $customer->mobile,
            'street' => $customer->street,
            'street2' => $customer->street2,
            'city' => $customer->city,
            'zip' => $customer->zip,
            'customer_rank' => 1,
            'company_type' => $customer->type === 'company' ? 'company' : 'person',
        ];

        $odooId = $this->odoo->create('res.partner', $partnerData);

        $customer->update([
            'odoo_partner_id' => $odooId,
            'odoo_synced_at' => now(),
        ]);

        Log::info('Created customer in Odoo', [
            'customer' => $customer->email,
            'odoo_id' => $odooId
        ]);

        return $odooId;
    }

    /**
     * Create or get customer from inquiry and sync to Odoo
     */
    public function createCustomerFromInquiry(Inquiry $inquiry): Customer
    {
        // Find or create local customer
        $customer = Customer::firstOrCreate(
            ['email' => $inquiry->customer_email],
            [
                'name' => $inquiry->customer_name,
                'phone' => $inquiry->customer_phone,
                'street' => $inquiry->delivery_address,
                'city' => $inquiry->delivery_city,
                'source' => 'inquiry',
            ]
        );

        // Link inquiry to customer
        if (!$inquiry->customer_id) {
            $inquiry->update(['customer_id' => $customer->id]);
        }

        // Sync to Odoo if not already synced
        if (!$customer->odoo_partner_id) {
            try {
                $this->pushCustomerToOdoo($customer);
            } catch (\Exception $e) {
                Log::error('Failed to sync inquiry customer to Odoo: ' . $e->getMessage());
            }
        }

        return $customer;
    }

    /**
     * Get sync status
     */
    public function getSyncStatus(): array
    {
        return [
            'total_customers' => Customer::count(),
            'synced_customers' => Customer::whereNotNull('odoo_partner_id')->count(),
            'unsynced_customers' => Customer::whereNull('odoo_partner_id')->count(),
            'last_sync' => Customer::whereNotNull('odoo_synced_at')
                ->orderBy('odoo_synced_at', 'desc')
                ->value('odoo_synced_at'),
            'from_inquiries' => Customer::where('source', 'inquiry')->count(),
            'from_odoo' => Customer::where('source', 'odoo')->count(),
        ];
    }
}
