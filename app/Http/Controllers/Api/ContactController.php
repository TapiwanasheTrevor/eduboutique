<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncContactFormToOdoo;
use App\Models\ContactForm;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Store a newly created contact form in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the contact form submission
        $contactForm = ContactForm::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'status' => 'new',
        ]);

        // Sync to Odoo as a CRM lead
        try {
            SyncContactFormToOdoo::dispatch($contactForm);
        } catch (\Exception $e) {
            // Log but don't fail the request if sync fails
            \Log::warning('Failed to dispatch Odoo sync job for contact form', [
                'contact_form_id' => $contactForm->id,
                'error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $contactForm->id,
                'created_at' => $contactForm->created_at,
            ],
            'message' => 'Thank you for contacting us. We\'ll respond within 24 hours.'
        ], 201);
    }
}
