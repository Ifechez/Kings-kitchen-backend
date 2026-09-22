<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    /** Public: list active plans (weekly/monthly/yearly), prices set by admin. */
    public function plans()
    {
        return response()->json(['plans' => MembershipPlan::where('is_active', true)->orderBy('price')->get()]);
    }

    /**
     * Customer requests premium membership. Two payment paths:
     *  - paystack: created as pending_approval + pending payment_status; frontend then calls
     *    PaymentController::initSubscription to get a checkout link, and it's auto-activated on verify.
     *  - cash: created as pending_approval; admin manually approves after receiving cash, from the admin panel.
     */
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'payment_method' => ['required', 'in:paystack,cash'],
        ]);

        $plan = MembershipPlan::findOrFail($data['membership_plan_id']);

        $subscription = Subscription::create([
            'user_id' => $request->user()->id,
            'membership_plan_id' => $plan->id,
            'amount_paid' => $plan->price,
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'status' => 'pending_approval',
        ]);

        return response()->json([
            'message' => $data['payment_method'] === 'cash'
                ? 'Request received. An admin will confirm your cash payment and activate your premium membership shortly.'
                : 'Subscription created. Proceed to payment to activate.',
            'subscription' => $subscription->load('plan'),
        ], 201);
    }

    public function mySubscriptions(Request $request)
    {
        return response()->json([
            'subscriptions' => $request->user()->subscriptions()->with('plan')->latest()->get(),
        ]);
    }
}
