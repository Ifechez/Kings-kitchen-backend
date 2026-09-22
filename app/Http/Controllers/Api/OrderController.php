<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PromoCode;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Create an order from the cart.
     *
     * Business rules applied server-side (never trust the frontend price):
     *  - Regular member's very first order => delivery_fee waived, subtotal untouched.
     *  - Active premium member => membership_plan discount_percent applied to every line.
     *  - Promo code discount is re-validated and recalculated here, never trusted from checkout.
     *  - delivery_fee comes from admin-configured Setting('delivery_fee').
     *  - scheduled_for is a customer preference shown to the admin; it does not
     *    automatically trigger anything (no background dispatcher exists yet).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'delivery_phone' => ['required', 'string', 'max:30'],
            'delivery_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:paystack,cash'],
            'promo_code' => ['nullable', 'string', 'max:30'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($data, $user) {
            $premiumDiscount = 0;
            if ($user->isPremiumActive()) {
                $premiumDiscount = $user->activeSubscription?->plan?->discount_percent ?? 0;
            }

            $isFreeFirstOrder = ! $user->free_first_order_used;

            $subtotal = 0;
            $lineItems = [];

            foreach ($data['items'] as $row) {
                $product = Product::findOrFail($row['product_id']);
                $base = $product->effectivePrice();
                $unitPrice = $premiumDiscount > 0
                    ? round($base - ($base * $premiumDiscount / 100), 2)
                    : $base;
                $lineTotal = round($unitPrice * $row['quantity'], 2);
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'unit_price' => $unitPrice,
                    'quantity' => $row['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            $deliveryFee = (float) Setting::get('delivery_fee', 1500);
            $discountTotal = 0;

            if ($isFreeFirstOrder) {
                $discountTotal += $deliveryFee;
                $deliveryFee = 0;
            }

            // Re-validate the promo code against the real subtotal, server-side.
            $promo = null;
            $promoDiscount = 0;
            if (! empty($data['promo_code'])) {
                $promo = PromoCode::whereRaw('UPPER(code) = ?', [strtoupper($data['promo_code'])])->first();
                if ($promo && $promo->isValidFor($subtotal)) {
                    $promoDiscount = $promo->discountFor($subtotal);
                } else {
                    $promo = null; // silently ignore an invalid/expired code rather than failing the whole order
                }
            }

            $total = round($subtotal + $deliveryFee - $promoDiscount, 2);

            $order = Order::create([
                'order_number' => 'KK-' . now()->format('Y') . '-' . str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount_total' => $discountTotal,
                'promo_code_id' => $promo?->id,
                'promo_discount' => $promoDiscount,
                'total' => $total,
                'is_free_first_order' => $isFreeFirstOrder,
                'delivery_address' => $data['delivery_address'],
                'delivery_phone' => $data['delivery_phone'],
                'delivery_lat' => $data['delivery_lat'] ?? null,
                'delivery_lng' => $data['delivery_lng'] ?? null,
                'note' => $data['note'] ?? null,
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending',
            ]);

            foreach ($lineItems as $li) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $li['product']->id,
                    'product_name' => $li['product']->name,
                    'unit_price' => $li['unit_price'],
                    'quantity' => $li['quantity'],
                    'line_total' => $li['line_total'],
                ]);
                $li['product']->increment('order_count', $li['quantity']);
            }

            if ($isFreeFirstOrder) {
                $user->update(['free_first_order_used' => true]);
            }

            if ($promo) {
                $promo->increment('used_count');
            }

            return response()->json([
                'message' => 'Order placed successfully.',
                'order' => $order->load('items'),
            ], 201);
        });
    }

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->with('items')->latest()->paginate(10);
        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        return response()->json([
            'order' => $order->load(['items', 'reviews']),
            'timeline' => $order->timeline(),
        ]);
    }
}
