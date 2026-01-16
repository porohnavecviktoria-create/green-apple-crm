<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Назва запчастини (напр. Дисплей iPhone 13)');
            $table->string('type')->nullable()->comment('Тип (акумулятор, екран тощо)');
            $table->decimal('cost_uah', 15, 2)->default(0)->comment('Собівартість запчастини');
            $table->string('serial_number')->nullable()->comment('Серійний номер запчастини');
            $table->string('status')->default('Stock')->comment('Stock, Installed, Broken');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
