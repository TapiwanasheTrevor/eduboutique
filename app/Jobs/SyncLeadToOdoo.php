<?php

namespace App\Jobs;

use App\Models\Inquiry;
use App\Services\OdooService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLeadToOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Inquiry $inquiry;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

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
            Log::info('Starting CRM lead sync to Odoo', [
                'inquiry_id' => $this->inquiry->id,
                'inquiry_number' => $this->inquiry->inquiry_number
            ]);

            // Check if lead already exists for this inquiry
            $existingLeads = $odoo->search(
                'crm.lead',
                [['name', 'ilike', $this->inquiry->inquiry_number]],
                ['id']
            );

            if (!empty($existingLeads)) {
                Log::info('Lead already exists in Odoo, skipping creation', [
                    'inquiry_number' => $this->inquiry->inquiry_number,
                    'lead_id' => $existingLeads[0]['id']
                ]);
                return;
            }

            // Build the lead description with cart items
            $description = $this->buildLeadDescription();

            // Create the CRM lead
            $leadData = [
                'name' => 'Website Inquiry: ' . $this->inquiry->inquiry_number,
                'contact_name' => $this->inquiry->customer_name,
                'email_from' => $this->inquiry->customer_email,
                'phone' => $this->inquiry->customer_phone,
                'description' => $description,
                'type' => 'lead', // 'lead' or 'opportunity'
                'street' => $this->inquiry->delivery_address,
                'city' => $this->inquiry->delivery_city,
            ];

            // Add expected revenue based on cart total
            if ($this->inquiry->total_usd) {
                $leadData['expected_revenue'] = $this->inquiry->total_usd;
            }

            // Try to get Zimbabwe country ID
            $countryId = $this->getZimbabweCountryId($odoo);
            if ($countryId) {
                $leadData['country_id'] = $countryId;
            }

            // Add source/medium tags
            $leadData['referred'] = 'EduBoutique Website';

            $leadId = $odoo->create('crm.lead', $leadData);

            // Update inquiry with lead ID (store in a separate field if needed)
            $this->inquiry->update([
                'odoo_lead_id' => $leadId,
            ]);

            Log::info('CRM lead created successfully in Odoo', [
                'inquiry_id' => $this->inquiry->id,
                'lead_id' => $leadId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync CRM lead to Odoo: ' . $e->getMessage(), [
                'inquiry_id' => $this->inquiry->id,
                'inquiry_number' => $this->inquiry->inquiry_number,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Build a detailed description for the lead.
     */
    protected function buildLeadDescription(): string
    {
        $lines = [];
        $lines[] = "=== Customer Inquiry Details ===\n";
        $lines[] = "Inquiry Number: " . $this->inquiry->inquiry_number;
        $lines[] = "Customer: " . $this->inquiry->customer_name;
        $lines[] = "Email: " . $this->inquiry->customer_email;
        $lines[] = "Phone: " . $this->inquiry->customer_phone;
        $lines[] = "";
        $lines[] = "Delivery Method: " . ucfirst(str_replace('_', ' ', $this->inquiry->delivery_method));

        if ($this->inquiry->delivery_address) {
            $lines[] = "Delivery Address: " . $this->inquiry->delivery_address;
        }
        if ($this->inquiry->delivery_city) {
            $lines[] = "City: " . $this->inquiry->delivery_city;
        }

        $lines[] = "\n=== Cart Items ===\n";

        if ($this->inquiry->cart_items && is_array($this->inquiry->cart_items)) {
            foreach ($this->inquiry->cart_items as $index => $item) {
                $productTitle = $item['title'] ?? $item['product_title'] ?? 'Product #' . ($item['product_id'] ?? $index + 1);
                $quantity = $item['quantity'] ?? 1;
                $priceUsd = $item['price_usd'] ?? 0;

                $lines[] = sprintf(
                    "%d. %s (Qty: %d) - $%.2f USD",
                    $index + 1,
                    $productTitle,
                    $quantity,
                    $priceUsd * $quantity
                );
            }
        }

        $lines[] = "\n=== Order Totals ===\n";
        $lines[] = sprintf("Total (USD): $%.2f", $this->inquiry->total_usd ?? 0);
        $lines[] = sprintf("Total (ZWL): $%.2f", $this->inquiry->total_zwl ?? 0);

        if ($this->inquiry->message) {
            $lines[] = "\n=== Customer Message ===\n";
            $lines[] = $this->inquiry->message;
        }

        $lines[] = "\n---";
        $lines[] = "Created: " . $this->inquiry->created_at->format('Y-m-d H:i:s');
        $lines[] = "Source: EduBoutique Website";

        return implode("\n", $lines);
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

            return null;

        } catch (\Exception $e) {
            Log::warning('Failed to get Zimbabwe country ID from Odoo', [
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncLeadToOdoo job failed permanently', [
            'inquiry_id' => $this->inquiry->id,
            'inquiry_number' => $this->inquiry->inquiry_number,
            'error' => $exception->getMessage()
        ]);
    }
}
