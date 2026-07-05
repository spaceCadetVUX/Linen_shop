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
        Schema::table('product_variants', function (Blueprint $table) {
            // Mirrors products.price/sale_price (VND) — flat column, not a
            // per-locale translations table, matching how VariantGeneratorService
            // already copies price straight from the product's flat columns.
            $table->decimal('price_usd', 12, 2)->nullable()->after('sale_price');
            $table->decimal('sale_price_usd', 12, 2)->nullable()->after('price_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['price_usd', 'sale_price_usd']);
        });
    }
};
