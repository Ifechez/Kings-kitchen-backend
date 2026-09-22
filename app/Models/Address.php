<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['user_id', 'label', 'address', 'phone', 'lat', 'lng', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'lat' => 'decimal:7', 'lng' => 'decimal:7'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
