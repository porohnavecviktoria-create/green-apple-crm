<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('repair_part', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_id')->constrained()->onDelete('cascade');
            $table->foreignId('part_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1)->comment('Кількість деталей');
            $table->decimal('cost_per_unit', 12, 2)->default(0)->comment('Собівартість за одиницю на момент використання');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_part');
    }
};
