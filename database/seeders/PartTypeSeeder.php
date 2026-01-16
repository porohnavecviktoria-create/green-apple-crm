<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PartType;

class PartTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            '📱 Дисплей',
            '🔋 Акумулятор',
            '📸 Камера',
            '📦 Корпус',
            '💎 Скло',
            '💧 Клей',
            '🎗 Шлейф',
            '🛠 До відновлення (биті)',
        ];

        foreach ($types as $type) {
            PartType::firstOrCreate(['name' => $type]);
        }
    }
}
