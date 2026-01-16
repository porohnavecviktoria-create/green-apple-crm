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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model');
            $table->string('imei')->nullable()->unique();
            $table->string('serial_number')->nullable()->unique();
            $table->string('storage')->nullable();
            $table->string('color')->nullable();
            $table->string('condition')->default('Used'); // New, Used, Scrap
            $table->string('status')->default('Stock'); // Stock, Repair, Sold, Scrap
            $table->decimal('purchase_cost', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
