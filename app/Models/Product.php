<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'price', 'discount_price',
        'image', 'is_available', 'is_hot', 'order_count', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_hot' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /** The price actually shown/charged to a given user (handles premium % off at controller level). */
    public function effectivePrice(): float
    {
        return $this->discount_price !== null ? (float) $this->discount_price : (float) $this->price;
    }

    protected function fullImageUrl(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            // New uploads (via Vercel Blob) store a full URL already — return as-is.
            // Anything else is a legacy local-disk path from the old cPanel `uploads`
            // disk, reconstructed the old way for backward compatibility.
            get: fn () => match (true) {
                ! $this->image => null,
                str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://') => $this->image,
                default => rtrim(config('app.url'), '/') . '/uploads/' . ltrim($this->image, '/'),
            },
        );
    }

    protected $appends = ['full_image_url'];
}
