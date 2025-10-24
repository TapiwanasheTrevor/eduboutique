<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Video;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Get featured products
        $featuredProducts = Product::where('featured', true)
            ->where('stock_status', '!=', 'out_of_stock')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Get new arrivals
        $newArrivals = Product::where('stock_status', '!=', 'out_of_stock')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Get popular categories
        $popularCategories = Category::has('products')
            ->withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(6)
            ->get();

        // Get latest videos
        $latestVideos = Video::where('published', true)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return Inertia::render('home/HomePage', [
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
            'popularCategories' => $popularCategories,
            'latestVideos' => $latestVideos,
        ]);
    }
}
