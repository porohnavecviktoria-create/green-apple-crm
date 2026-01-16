<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'batch_id',
        'contractor_id',
        'warehouse_id',
        'category_id',
        'subcategory_id',
        'model',
        'imei',
        'serial_number',
        'marker',
        'storage',
        'color',
        'condition',
        'status',
        'lock_status',
        'purchase_cost',
        'selling_price',
        'description',
        'purchase_currency',
        'purchase_price_currency',
        'exchange_rate',
        'additional_costs',
        'additional_costs_note'
    ];

    public function batch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function contractor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function warehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function parts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Part::class)->withPivot('quantity');
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Sale::class, 'saleable');
    }
}
