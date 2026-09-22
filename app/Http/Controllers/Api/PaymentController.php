<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /** Initialize a Paystack transaction for an existing order and return the checkout authorization_url. */
    public function initOrderPayment(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->payment_method !== 'paystack') {
            return response()->json(['message' => 'This order is not set for card/transfer payment.'], 422);
        }

        $reference = 'ORD-' . $order->order_number . '-' . time();

        $response = Http::withToken(config('services.paystack.secret'))
            ->post(config('services.paystack.url') . '/transaction/initialize', [
                'email' => $request->user()->email,
                'amount' => (int) round($order->total * 100), // kobo
                'reference' => $reference,
                'callback_url' => config('app.frontend_url') . '/payment/callback?type=order&id=' . $order->id,
                'metadata' => ['order_id' => $order->id, 'type' => 'order'],
            ]);

        if (! $response->successful()) {
            Log::error('Paystack init failed', ['body' => $response->body()]);
            return response()->json(['message' => 'Could not start payment. Please try again.'], 502);
        }

        $order->update(['payment_reference' => $reference]);

        return response()->json(['authorization_url' => $response->json('data.authorization_url')]);
    }

    /** Initialize a Paystack transaction for a premium subscription. */
    public function initSubscriptionPayment(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);

        $reference = 'SUB-' . $subscription->id . '-' . time();

        $response = Http::withToken(config('services.paystack.secret'))
            ->post(config('services.paystack.url') . '/transaction/initialize', [
                'email' => $request->user()->email,
                'amount' => (int) round($subscription->amount_paid * 100),
                'reference' => $reference,
                'callback_url' => config('app.frontend_url') . '/payment/callback?type=subscription&id=' . $subscription->id,
                'metadata' => ['subscription_id' => $subscription->id, 'type' => 'subscription'],
            ]);

        if (! $response->successful()) {
            Log::error('Paystack init failed', ['body' => $response->body()]);
            return response()->json(['message' => 'Could not start payment. Please try again.'], 502);
        }

        $subscription->update(['payment_reference' => $reference]);

        return response()->json(['authorization_url' => $response->json('data.authorization_url')]);
    }

    /** Called by the frontend on the callback page to verify + finalize a transaction. */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string'],
            'type' => ['required', 'in:order,subscription'],
        ]);

        $response = Http::withToken(config('services.paystack.secret'))
            ->get(config('services.paystack.url') . '/transaction/verify/' . $data['reference']);

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            return response()->json(['message' => 'Payment could not be verified.', 'status' => 'failed'], 422);
        }

        if ($data['type'] === 'order') {
            $order = Order::where('payment_reference', $data['reference'])->firstOrFail();
            $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);
            return response()->json(['message' => 'Payment confirmed. Your order is being prepared!', 'order' => $order]);
        }

        $subscription = Subscription::where('payment_reference', $data['reference'])->firstOrFail();
        $subscription->load('plan');
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

        return response()->json(['message' => 'Welcome to Royal Premium! Your discount is now active.', 'subscription' => $subscription]);
    }

    /** Paystack webhook (server-to-server backup confirmation, in case the customer closes the tab). */
    public function webhook(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $expected = hash_hmac('sha512', $payload, config('services.paystack.secret'));

        if (! $signature || ! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = $request->input('event');
        $reference = $request->input('data.reference');

        if ($event === 'charge.success' && $reference) {
            if (str_starts_with($reference, 'ORD-')) {
                Order::where('payment_reference', $reference)
                    ->update(['payment_status' => 'paid', 'status' => 'confirmed']);
            } elseif (str_starts_with($reference, 'SUB-')) {
                $subscription = Subscription::where('payment_reference', $reference)->first();
                if ($subscription && $subscription->status !== 'active') {
                    $subscription->load('plan');
                    $starts = now();
                    $ends = match ($subscription->plan->billing_cycle) {
                        'weekly' => $starts->copy()->addWeek(),
                        'monthly' => $starts->copy()->addMonth(),
                        'yearly' => $starts->copy()->addYear(),
                    };
                    $subscription->update(['payment_status' => 'paid', 'status' => 'active', 'starts_at' => $starts, 'ends_at' => $ends]);
                    $subscription->user->update(['membership' => 'premium', 'premium_expires_at' => $ends]);
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
