<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show($slug)
    {
        $product = Product::with(['category'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Get related products (same category or subject)
        $relatedProducts = Product::where('id', '!=', $product->id)
            ->where('stock_status', '!=', 'out_of_stock')
            ->where(function($query) use ($product) {
                $query->where('category_id', $product->category_id)
                    ->orWhere('subject', $product->subject);
            })
            ->limit(4)
            ->get();

        return Inertia::render('product/ProductDetailPage', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
