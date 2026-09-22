<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * A customer can only review a product they actually received, on a
     * delivered order — this is "verified purchase" enforcement, not decorative.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'product_id' => ['required', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::findOrFail($data['order_id']);
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless($order->status === 'delivered', 422, 'You can only review items from a delivered order.');
        abort_unless($order->items()->where('product_id', $data['product_id'])->exists(), 422, 'That item was not part of this order.');

        $review = Review::updateOrCreate(
            ['order_id' => $data['order_id'], 'product_id' => $data['product_id']],
            ['user_id' => $request->user()->id, 'rating' => $data['rating'], 'comment' => $data['comment'] ?? null]
        );

        return response()->json(['review' => $review], 201);
    }

    /** Public: recent reviews for a given product, shown under the menu item. */
    public function forProduct(int $productId)
    {
        $reviews = Review::with('user:id,name')
            ->where('product_id', $productId)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json(['reviews' => $reviews]);
    }
}
