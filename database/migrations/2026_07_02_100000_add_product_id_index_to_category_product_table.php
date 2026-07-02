<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Composite PK (category_id, product_id) only serves lookups that lead
        // with category_id — $product->categories() filters by product_id alone
        // and was falling back to a sequential scan.
        Schema::table('category_product', function (Blueprint $table) {
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('category_product', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
        });
    }
};
