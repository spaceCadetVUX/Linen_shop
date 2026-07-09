<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id']);

            $table->uuid('product_variant_id')->nullable()->after('product_id');

            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->nullOnDelete();

            $table->index('product_variant_id');

            // NULL != NULL in Postgres unique indexes, so a variant-less product
            // (product_variant_id always NULL) would never collide with itself
            // here — that's fine, CartService::addItem()/CartRepository::findItem()
            // already do the product_id+variant_id "find existing line" lookup at
            // the application level before writing, this index is a backstop.
            $table->unique(['cart_id', 'product_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id', 'product_variant_id']);
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
            $table->unique(['cart_id', 'product_id']);
        });
    }
};
