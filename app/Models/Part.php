<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $fillable = [
        'name',
        'part_type_id', // Додано
        'type', // Залишаємо для сумісності поки що
        'cost_uah',
        'quantity',
        'contractor_id',
        'serial_number',
        'status',
        'description'
    ];

    protected static function booted()
    {
        static::creating(function ($part) {
            if (empty($part->serial_number)) {
                $part->serial_number = rand(1000, 9999);
            }
        });
    }

    public function partType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PartType::class, 'part_type_id');
    }

    public function contractor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function devices(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Device::class);
    }

    // Реставрація: запчастини, які входять у цю запчастину
    public function subParts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'part_part', 'parent_id', 'child_id');
    }

    // Аліас для сумісності з Filament (якщо він шукає parts)
    public function parts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->subParts();
    }

    // Запчастини, у які входить ця запчастина
    public function parentParts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'part_part', 'child_id', 'parent_id');
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->partType) {
            return $this->partType->name;
        }

        return match ($this->type) {
            'Display' => '📱 Дисплей',
            'Battery' => '🔋 Батарея',
            'Camera' => '📸 Камера',
            'Body' => '📦 Корпус',
            default => '🛠 Запчастина',
        };
    }

    public function sales(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Sale::class, 'saleable');
    }
}
