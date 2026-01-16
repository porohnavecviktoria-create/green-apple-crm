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
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('phone_model')->comment('Модель телефону');
            $table->string('imei')->nullable()->comment('IMEI телефону');
            $table->text('problem_description')->nullable()->comment('Опис проблеми');
            $table->decimal('repair_cost', 12, 2)->default(0)->comment('Вартість ремонту');
            $table->decimal('parts_cost', 12, 2)->default(0)->comment('Собівартість деталей');
            $table->decimal('profit', 12, 2)->default(0)->comment('Прибуток');
            $table->string('status')->default('pending')->comment('pending, in_progress, completed, issued');
            $table->text('description')->nullable()->comment('Додатковий опис');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
