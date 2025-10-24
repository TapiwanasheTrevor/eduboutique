<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InquiryController extends Controller
{
    /**
     * Store a newly created inquiry in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:50',
            'delivery_method' => 'required|in:store_pickup,agent_delivery',
            'delivery_address' => 'required_if:delivery_method,agent_delivery|nullable|string',
            'delivery_city' => 'required_if:delivery_method,agent_delivery|nullable|string|max:100',
            'message' => 'nullable|string',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.product_id' => 'required|uuid|exists:products,id',
            'cart_items.*.quantity' => 'required|integer|min:1',
            'cart_items.*.price_zwl' => 'required|numeric|min:0',
            'cart_items.*.price_usd' => 'required|numeric|min:0',
            'total_zwl' => 'required|numeric|min:0',
            'total_usd' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate unique inquiry number
        $inquiryNumber = $this->generateInquiryNumber();

        // Create the inquiry
        $inquiry = Inquiry::create([
            'inquiry_number' => $inquiryNumber,
            'customer_name' => $request->input('customer_name'),
            'customer_email' => $request->input('customer_email'),
            'customer_phone' => $request->input('customer_phone'),
            'delivery_method' => $request->input('delivery_method'),
            'delivery_address' => $request->input('delivery_address'),
            'delivery_city' => $request->input('delivery_city'),
            'message' => $request->input('message'),
            'cart_items' => $request->input('cart_items'),
            'total_zwl' => $request->input('total_zwl'),
            'total_usd' => $request->input('total_usd'),
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => [
                'id' => $inquiry->id,
                'inquiry_number' => $inquiry->inquiry_number,
                'status' => $inquiry->status,
                'created_at' => $inquiry->created_at,
            ],
            'message' => 'Inquiry submitted successfully. We\'ll contact you shortly.'
        ], 201);
    }

    /**
     * Generate a unique inquiry number in the format INQ-YYYYNNNNN
     *
     * @return string
     */
    private function generateInquiryNumber(): string
    {
        $year = date('Y');
        $prefix = "INQ-{$year}";

        // Get the last inquiry number for this year
        $lastInquiry = Inquiry::where('inquiry_number', 'LIKE', "{$prefix}%")
            ->orderBy('inquiry_number', 'desc')
            ->first();

        if ($lastInquiry) {
            // Extract the sequence number and increment it
            $lastNumber = intval(substr($lastInquiry->inquiry_number, -5));
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            // First inquiry of the year
            $newNumber = '00001';
        }

        $inquiryNumber = "{$prefix}{$newNumber}";

        // Check if this number already exists (just in case)
        if (Inquiry::where('inquiry_number', $inquiryNumber)->exists()) {
            // Recursively generate a new number
            return $this->generateInquiryNumber();
        }

        return $inquiryNumber;
    }
}
