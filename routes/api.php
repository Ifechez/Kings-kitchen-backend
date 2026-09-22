<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\MembershipController as AdminMembershipController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\PromoCodeController as AdminPromoCodeController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\PublicSettingController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

// --- Public ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/settings', [PublicSettingController::class, 'index']);
Route::get('/menu', [ProductController::class, 'index']);
Route::get('/menu/{product}', [ProductController::class, 'show']);
Route::get('/menu/{product}/reviews', [ReviewController::class, 'forProduct']);
Route::get('/membership/plans', [MembershipController::class, 'plans']);
Route::post('/paystack/webhook', [PaymentController::class, 'webhook']);

// --- Authenticated customer ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/pay', [PaymentController::class, 'initOrderPayment']);

    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::put('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);

    Route::post('/promo/validate', [PromoController::class, 'validateCode']);
    Route::post('/reviews', [ReviewController::class, 'store']);

    Route::post('/membership/subscribe', [MembershipController::class, 'subscribe']);
    Route::get('/membership/my-subscriptions', [MembershipController::class, 'mySubscriptions']);
    Route::post('/membership/subscriptions/{subscription}/pay', [PaymentController::class, 'initSubscriptionPayment']);

    Route::post('/payment/verify', [PaymentController::class, 'verify']);
});

// --- Admin only ---
Route::middleware(['auth:sanctum', 'is_admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChart']);

    Route::apiResource('products', AdminProductController::class)->except(['show']);

    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);

    Route::get('/customers', [AdminCustomerController::class, 'index']);

    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::put('/orders/{order}', [AdminOrderController::class, 'update']);
    Route::post('/orders/{order}/location', [AdminOrderController::class, 'updateLocation']);

    Route::get('/membership-plans', [AdminMembershipController::class, 'plans']);
    Route::post('/membership-plans', [AdminMembershipController::class, 'storePlan']);
    Route::put('/membership-plans/{plan}', [AdminMembershipController::class, 'updatePlan']);

    Route::get('/subscriptions', [AdminMembershipController::class, 'subscriptions']);
    Route::post('/subscriptions/{subscription}/approve', [AdminMembershipController::class, 'approveSubscription']);
    Route::post('/subscriptions/{subscription}/reject', [AdminMembershipController::class, 'rejectSubscription']);

    Route::get('/promo-codes', [AdminPromoCodeController::class, 'index']);
    Route::post('/promo-codes', [AdminPromoCodeController::class, 'store']);
    Route::put('/promo-codes/{promoCode}', [AdminPromoCodeController::class, 'update']);
    Route::delete('/promo-codes/{promoCode}', [AdminPromoCodeController::class, 'destroy']);

    Route::get('/reviews', [AdminReviewController::class, 'index']);
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy']);

    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);
});
