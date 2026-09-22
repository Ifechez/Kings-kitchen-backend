<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        return response()->json(
            Review::with(['user:id,name', 'product:id,name'])->latest()->paginate(20)
        );
    }

    /** Moderation: remove an inappropriate or spam review. */
    public function destroy(Review $review)
    {
        $review->delete();
        return response()->json(['message' => 'Review removed.']);
    }
}
