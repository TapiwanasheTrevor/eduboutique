<?php

namespace App\Jobs;

use App\Models\ContactForm;
use App\Services\OdooService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncContactFormToOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ContactForm $contactForm;

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
    public function __construct(ContactForm $contactForm)
    {
        $this->contactForm = $contactForm;
    }

    /**
     * Execute the job.
     */
    public function handle(OdooService $odoo): void
    {
        try {
            Log::info('Starting contact form sync to Odoo CRM', [
                'contact_form_id' => $this->contactForm->id,
                'email' => $this->contactForm->email
            ]);

            // Check if already synced
            if ($this->contactForm->odoo_lead_id) {
                Log::info('Contact form already synced to Odoo', [
                    'contact_form_id' => $this->contactForm->id,
                    'odoo_lead_id' => $this->contactForm->odoo_lead_id
                ]);
                return;
            }

            // Build the lead description
            $description = $this->buildLeadDescription();

            // Create the CRM lead
            $leadData = [
                'name' => 'Website Contact: ' . $this->contactForm->subject,
                'contact_name' => $this->contactForm->name,
                'email_from' => $this->contactForm->email,
                'phone' => $this->contactForm->phone,
                'description' => $description,
                'type' => 'lead',
                'referred' => 'EduBoutique Website - Contact Form',
            ];

            // Try to get Zimbabwe country ID
            $countryId = $this->getZimbabweCountryId($odoo);
            if ($countryId) {
                $leadData['country_id'] = $countryId;
            }

            $leadId = $odoo->create('crm.lead', $leadData);

            // Update contact form with lead ID
            $this->contactForm->update([
                'odoo_lead_id' => $leadId,
                'odoo_synced_at' => now(),
            ]);

            Log::info('Contact form synced to Odoo CRM successfully', [
                'contact_form_id' => $this->contactForm->id,
                'lead_id' => $leadId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync contact form to Odoo: ' . $e->getMessage(), [
                'contact_form_id' => $this->contactForm->id,
                'exception' => $e,
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
        $lines[] = "=== Contact Form Submission ===\n";
        $lines[] = "Subject: " . $this->contactForm->subject;
        $lines[] = "Name: " . $this->contactForm->name;
        $lines[] = "Email: " . $this->contactForm->email;
        $lines[] = "Phone: " . $this->contactForm->phone;
        $lines[] = "";
        $lines[] = "=== Message ===\n";
        $lines[] = $this->contactForm->message;
        $lines[] = "\n---";
        $lines[] = "Submitted: " . $this->contactForm->created_at->format('Y-m-d H:i:s');
        $lines[] = "Source: EduBoutique Website - Contact Form";

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
        Log::error('SyncContactFormToOdoo job failed permanently', [
            'contact_form_id' => $this->contactForm->id,
            'error' => $exception->getMessage()
        ]);
    }
}
