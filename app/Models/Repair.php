<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Repair extends Model
{
    protected $fillable = [
        'customer_id',
        'phone_model',
        'imei',
        'problem_description',
        'repair_cost',
        'parts_cost',
        'profit',
        'status',
        'description',
        'completed_at',
        'issued_at',
    ];

    protected $casts = [
        'repair_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'profit' => 'decimal:2',
        'completed_at' => 'datetime',
        'issued_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'repair_part')
            ->withPivot('quantity', 'cost_per_unit')
            ->withTimestamps();
    }

    /**
     * Автоматично розраховує собівартість деталей та прибуток
     */
    public function calculateCosts(): void
    {
        $partsCost = $this->parts()->sum(function ($part) {
            return $part->pivot->cost_per_unit * $part->pivot->quantity;
        });
        
        $this->parts_cost = $partsCost;
        $this->profit = $this->repair_cost - $partsCost;
    }
}
