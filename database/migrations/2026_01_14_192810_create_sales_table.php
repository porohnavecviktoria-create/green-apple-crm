<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('saleable'); // saleable_id та saleable_type
            $table->integer('quantity')->default(1);
            $table->decimal('buy_price', 12, 2);
            $table->decimal('sell_price', 12, 2);
            $table->decimal('profit', 12, 2);
            $table->timestamp('sold_at')->useCurrent();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
