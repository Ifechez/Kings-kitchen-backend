<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** Public menu listing, grouped by category, with per-user effective pricing + ratings. */
    public function index(Request $request)
    {
        $user = $request->user(); // may be null (guest)
        $premiumDiscount = 0;

        if ($user && $user->isPremiumActive()) {
            $plan = $user->activeSubscription?->plan;
            $premiumDiscount = $plan?->discount_percent ?? 0;
        }

        $categories = Category::with(['products' => function ($q) {
            $q->where('is_available', true)
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderBy('sort_order');
        }])->orderBy('sort_order')->get();

        $categories->each(function ($category) use ($premiumDiscount) {
            $category->products->each(function ($product) use ($premiumDiscount) {
                $base = $product->effectivePrice();
                $final = $premiumDiscount > 0
                    ? round($base - ($base * $premiumDiscount / 100), 2)
                    : $base;
                $product->final_price = $final;
                $product->has_premium_discount = $premiumDiscount > 0;
            });
        });

        return response()->json([
            'categories' => $categories,
            'hot_items' => Product::where('is_hot', true)->where('is_available', true)
                ->withAvg('reviews', 'rating')->withCount('reviews')
                ->limit(8)->get(),
        ]);
    }

    public function show(Product $product)
    {
        $product->loadAvg('reviews', 'rating');
        $product->loadCount('reviews');
        return response()->json(['product' => $product]);
    }
}
