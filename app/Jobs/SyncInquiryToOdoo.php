<?php

namespace App\Jobs;

use App\Models\Inquiry;
use App\Models\Product;
use App\Services\OdooService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInquiryToOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Inquiry $inquiry;

    /**
     * Create a new job instance.
     */
    public function __construct(Inquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    /**
     * Execute the job.
     */
    public function handle(OdooService $odoo): void
    {
        try {
            Log::info('Starting inquiry sync to Odoo', [
                'inquiry_id' => $this->inquiry->id,
                'inquiry_number' => $this->inquiry->inquiry_number
            ]);

            // First, create or get the customer (partner) in Odoo
            $partnerId = $this->syncCustomer($odoo);

            // Then create the sales order
            $orderId = $this->createSalesOrder($odoo, $partnerId);

            // Update the inquiry with Odoo order ID
            $this->inquiry->update([
                'odoo_order_id' => $orderId,
                'odoo_synced_at' => now(),
            ]);

            Log::info('Inquiry synced successfully to Odoo', [
                'inquiry_id' => $this->inquiry->id,
                'odoo_order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync inquiry to Odoo: ' . $e->getMessage(), [
                'inquiry_id' => $this->inquiry->id,
                'inquiry_number' => $this->inquiry->inquiry_number,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sync customer to Odoo (res.partner).
     */
    protected function syncCustomer(OdooService $odoo): int
    {
        try {
            // Search for existing customer by email
            $partners = $odoo->search(
                'res.partner',
                [['email', '=', $this->inquiry->customer_email]],
                ['id', 'name', 'email']
            );

            if (!empty($partners)) {
                Log::info('Found existing customer in Odoo', [
                    'partner_id' => $partners[0]['id'],
                    'email' => $this->inquiry->customer_email
                ]);

                return $partners[0]['id'];
            }

            // Create new customer
            $customerData = [
                'name' => $this->inquiry->customer_name,
                'email' => $this->inquiry->customer_email,
                'phone' => $this->inquiry->customer_phone,
                'street' => $this->inquiry->delivery_address,
                'city' => $this->inquiry->delivery_city,
            ];

            // Try to get Zimbabwe country ID
            $countryId = $this->getZimbabweCountryId($odoo);
            if ($countryId) {
                $customerData['country_id'] = $countryId;
            }

            $partnerId = $odoo->create('res.partner', $customerData);

            Log::info('Created new customer in Odoo', [
                'partner_id' => $partnerId,
                'email' => $this->inquiry->customer_email
            ]);

            return $partnerId;

        } catch (\Exception $e) {
            Log::error('Failed to sync customer to Odoo', [
                'email' => $this->inquiry->customer_email,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create sales order in Odoo.
     */
    protected function createSalesOrder(OdooService $odoo, int $partnerId): int
    {
        try {
            $orderLines = [];

            // Map cart items to Odoo order line format
            foreach ($this->inquiry->cart_items as $item) {
                $product = Product::find($item['product_id']);

                if ($product && $product->odoo_product_id) {
                    // Odoo order line format: [0, 0, {values}]
                    // 0, 0 means create a new line
                    $orderLines[] = [
                        0, 0, [
                            'product_id' => $product->odoo_product_id,
                            'product_uom_qty' => $item['quantity'],
                            'price_unit' => $item['price_usd'] ?? $product->price_usd,
                        ]
                    ];

                    Log::debug('Added order line', [
                        'product' => $product->title,
                        'quantity' => $item['quantity'],
                        'odoo_product_id' => $product->odoo_product_id
                    ]);
                } else {
                    Log::warning('Product not found or not synced to Odoo', [
                        'product_id' => $item['product_id']
                    ]);
                }
            }

            if (empty($orderLines)) {
                throw new \Exception('No valid order lines to sync to Odoo');
            }

            // Create the sales order
            $orderData = [
                'partner_id' => $partnerId,
                'origin' => $this->inquiry->inquiry_number,
                'client_order_ref' => $this->inquiry->inquiry_number,
                'order_line' => $orderLines,
            ];

            // Add notes/message if available
            if ($this->inquiry->message) {
                $orderData['note'] = $this->inquiry->message;
            }

            $orderId = $odoo->create('sale.order', $orderData);

            Log::info('Created sales order in Odoo', [
                'order_id' => $orderId,
                'partner_id' => $partnerId,
                'line_count' => count($orderLines)
            ]);

            return $orderId;

        } catch (\Exception $e) {
            Log::error('Failed to create sales order in Odoo', [
                'partner_id' => $partnerId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get Zimbabwe country ID from Odoo.
     */
    protected function getZimbabweCountryId(OdooService $odoo): ?int
    {
        try {
            $countries = $odoo->search(
                'res.country',
                [['code', '=', 'ZW']],
                ['id']
            );

            if (!empty($countries)) {
                return $countries[0]['id'];
            }

            Log::warning('Zimbabwe country not found in Odoo');
            return null;

        } catch (\Exception $e) {
            Log::warning('Failed to get Zimbabwe country ID from Odoo', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
