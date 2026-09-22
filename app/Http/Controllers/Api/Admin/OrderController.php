<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user:id,name,member_id,phone', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('member_id', 'like', "%{$s}%"));
            });
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:pending,confirmed,preparing,out_for_delivery,delivered,cancelled'],
            'payment_status' => ['sometimes', 'in:pending,paid,failed'], // e.g. admin marks cash order as paid on delivery
        ]);

        // Stamp the timestamp for whichever stage we're moving into, so the
        // customer's tracking timeline shows real times ("Confirmed at 10:32am"...).
        if (isset($data['status'])) {
            $stampColumn = match ($data['status']) {
                'confirmed' => 'confirmed_at',
                'preparing' => 'preparing_at',
                'out_for_delivery' => 'out_for_delivery_at',
                'delivered' => 'delivered_at',
                'cancelled' => 'cancelled_at',
                default => null,
            };
            if ($stampColumn && ! $order->{$stampColumn}) {
                $data[$stampColumn] = now();
            }
        }

        $order->update($data);

        return response()->json(['order' => $order]);
    }

    /**
     * Called repeatedly from the admin panel (via navigator.geolocation.watchPosition)
     * by whoever is physically delivering the order, so the customer sees a live
     * moving pin on their tracking map. This is real GPS data from a real phone —
     * there's no separate courier fleet system, just the delivery person's browser.
     */
    public function updateLocation(Request $request, Order $order)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $order->update([
            'courier_lat' => $data['lat'],
            'courier_lng' => $data['lng'],
            'courier_updated_at' => now(),
        ]);

        return response()->json(['message' => 'Location updated.']);
    }
}
