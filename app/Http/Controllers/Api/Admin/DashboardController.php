<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** Top summary cards: today / this week / this month / this year, one click. */
    public function summary(Request $request)
    {
        $paid = Order::where('payment_status', 'paid')->orWhere(function ($q) {
            $q->where('payment_method', 'cash')->whereIn('status', ['confirmed', 'preparing', 'out_for_delivery', 'delivered']);
        });

        $sumFor = fn ($from) => (clone $paid)->where('created_at', '>=', $from)->sum('total');
        $countFor = fn ($from) => (clone $paid)->where('created_at', '>=', $from)->count();

        return response()->json([
            'sales' => [
                'today' => ['revenue' => $sumFor(now()->startOfDay()), 'orders' => $countFor(now()->startOfDay())],
                'this_week' => ['revenue' => $sumFor(now()->startOfWeek()), 'orders' => $countFor(now()->startOfWeek())],
                'this_month' => ['revenue' => $sumFor(now()->startOfMonth()), 'orders' => $countFor(now()->startOfMonth())],
                'this_year' => ['revenue' => $sumFor(now()->startOfYear()), 'orders' => $countFor(now()->startOfYear())],
                'all_time' => ['revenue' => (clone $paid)->sum('total'), 'orders' => (clone $paid)->count()],
            ],
            'totals' => [
                'products' => Product::count(),
                'customers' => User::where('role', 'customer')->count(),
                'premium_members' => User::where('membership', 'premium')->count(),
                'pending_orders' => Order::whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
                'pending_subscription_approvals' => Subscription::where('status', 'pending_approval')->count(),
            ],
            'top_products' => Product::orderByDesc('order_count')->limit(5)->get(['id', 'name', 'order_count', 'is_hot']),
            'recent_orders' => Order::with('user:id,name,member_id')->latest()->limit(8)->get(),
        ]);
    }

    /** Revenue chart data: daily points for the given range (default 30 days). */
    public function salesChart(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $rows = Order::selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->where(function ($q) {
                $q->where('payment_status', 'paid')->orWhere('payment_method', 'cash');
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['chart' => $rows]);
    }
}
