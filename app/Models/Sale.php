<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'saleable_id',
        'saleable_type',
        'quantity',
        'buy_price',
        'sell_price',
        'profit',
        'sold_at',
        'description'
    ];

    protected $casts = [
        'sold_at' => 'datetime',
    ];

    public function saleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
