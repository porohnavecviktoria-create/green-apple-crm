<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartType extends Model
{
    protected $fillable = ['name'];

    public function parts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Part::class);
    }

    /**
     * Автоматично додаємо емодзі до назви, якщо його там ще немає
     */
    protected function setNameAttribute($value)
    {
        $emojiMap = [
            'дисплей' => '📱',
            'екран' => '📱',
            'акумулятор' => '🔋',
            'батарея' => '🔋',
            'камера' => '📸',
            'корпус' => '📦',
            'скло' => '💎',
            'клей' => '💧',
            'шлейф' => '🎗',
            'динамік' => '🔊',
            'мікрофон' => '🎤',
            'гніздо' => '🔌',
            'кнопка' => '🔘',
        ];

        $lowerValue = mb_strtolower($value);
        $emoji = '';

        foreach ($emojiMap as $keyword => $icon) {
            if (mb_strpos($lowerValue, $keyword) !== false) {
                $emoji = $icon . ' ';
                break;
            }
        }

        // Якщо назва вже починається з емодзі (будь-якого), не додаємо
        if (!preg_match('/^[\x{1F300}-\x{1F9FF}]/u', $value)) {
            $this->attributes['name'] = $emoji . $value;
        } else {
            $this->attributes['name'] = $value;
        }
    }
}
