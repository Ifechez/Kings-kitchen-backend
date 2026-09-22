<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'subtotal', 'delivery_fee', 'discount_total', 'total',
        'is_free_first_order', 'delivery_address', 'delivery_phone', 'note',
        'delivery_lat', 'delivery_lng',
        'payment_method', 'payment_status', 'payment_reference', 'status',
        'confirmed_at', 'preparing_at', 'out_for_delivery_at', 'delivered_at', 'cancelled_at',
        'courier_lat', 'courier_lng', 'courier_updated_at',
        'scheduled_for', 'promo_code_id', 'promo_discount',
    ];

    protected function casts(): array
    {
        return [
            'is_free_first_order' => 'boolean',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'promo_discount' => 'decimal:2',
            'total' => 'decimal:2',
            'delivery_lat' => 'decimal:7',
            'delivery_lng' => 'decimal:7',
            'courier_lat' => 'decimal:7',
            'courier_lng' => 'decimal:7',
            'confirmed_at' => 'datetime',
            'preparing_at' => 'datetime',
            'out_for_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'courier_updated_at' => 'datetime',
            'scheduled_for' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** Ordered list of {key, label, timestamp} for the customer-facing tracking timeline. */
    public function timeline(): array
    {
        $steps = [
            ['key' => 'pending', 'label' => 'Order Placed', 'timestamp' => $this->created_at],
            ['key' => 'confirmed', 'label' => 'Confirmed', 'timestamp' => $this->confirmed_at],
            ['key' => 'preparing', 'label' => 'Preparing', 'timestamp' => $this->preparing_at],
            ['key' => 'out_for_delivery', 'label' => 'Out for Delivery', 'timestamp' => $this->out_for_delivery_at],
            ['key' => 'delivered', 'label' => 'Delivered', 'timestamp' => $this->delivered_at],
        ];

        if ($this->status === 'cancelled') {
            return [
                $steps[0],
                ['key' => 'cancelled', 'label' => 'Cancelled', 'timestamp' => $this->cancelled_at],
            ];
        }

        return $steps;
    }
}
