<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    // --- Plans (admin sets the weekly/monthly/yearly prices + discount %) ---

    public function plans()
    {
        return response()->json(['plans' => MembershipPlan::orderBy('price')->get()]);
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'billing_cycle' => ['required', 'in:weekly,monthly,yearly'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);
        $plan = MembershipPlan::create($data);
        return response()->json(['plan' => $plan], 201);
    }

    public function updatePlan(Request $request, MembershipPlan $plan)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'billing_cycle' => ['sometimes', 'in:weekly,monthly,yearly'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'discount_percent' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);
        $plan->update($data);
        return response()->json(['plan' => $plan]);
    }

    // --- Subscription approvals (mainly for cash payments) ---

    public function subscriptions(Request $request)
    {
        $query = Subscription::with(['user:id,name,member_id,phone', 'plan']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return response()->json($query->latest()->paginate(15));
    }

    /** Admin confirms a cash payment for premium membership -> activates it immediately. */
    public function approveSubscription(Subscription $subscription)
    {
        $subscription->load('plan', 'user');
        $starts = now();
        $ends = match ($subscription->plan->billing_cycle) {
            'weekly' => $starts->copy()->addWeek(),
            'monthly' => $starts->copy()->addMonth(),
            'yearly' => $starts->copy()->addYear(),
        };

        $subscription->update([
            'payment_status' => 'paid',
            'status' => 'active',
            'starts_at' => $starts,
            'ends_at' => $ends,
        ]);

        $subscription->user->update(['membership' => 'premium', 'premium_expires_at' => $ends]);

        return response()->json(['message' => 'Membership activated.', 'subscription' => $subscription]);
    }

    public function rejectSubscription(Subscription $subscription)
    {
        $subscription->update(['status' => 'rejected']);
        return response()->json(['message' => 'Subscription request rejected.']);
    }
}
