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
        Schema::table('devices', function (Blueprint $table) {
            $table->string('marker')->nullable()->after('serial_number');
            $table->foreignId('contractor_id')->nullable()->after('batch_id')->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('contractor_id')->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained()->nullOnDelete();

            $table->string('purchase_currency')->default('USD')->after('selling_price');
            $table->decimal('purchase_price_currency', 12, 2)->default(0)->after('purchase_currency');
            $table->decimal('exchange_rate', 12, 4)->default(1)->after('purchase_price_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            //
        });
    }
};
