<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    /**
     * Checked live as the customer types a code at checkout, BEFORE the order is placed.
     * The order is re-validated and the code re-applied server-side on submit — this
     * endpoint is just for instant UI feedback, never trusted for the actual charge.
     */
    public function validateCode(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $promo = PromoCode::whereRaw('UPPER(code) = ?', [strtoupper($data['code'])])->first();

        if (! $promo || ! $promo->isValidFor((float) $data['subtotal'])) {
            return response()->json(['valid' => false, 'message' => 'This code is invalid, expired, or the order total is too low.'], 422);
        }

        return response()->json([
            'valid' => true,
            'code' => $promo->code,
            'discount' => $promo->discountFor((float) $data['subtotal']),
            'discount_type' => $promo->discount_type,
            'discount_value' => $promo->discount_value,
        ]);
    }
}
