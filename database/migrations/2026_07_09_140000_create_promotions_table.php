<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->string('title', 150);
            $table->string('title_en', 150)->nullable();
            $table->string('banner_image')->nullable();

            $table->string('cta_label', 60)->nullable();
            $table->string('cta_label_en', 60)->nullable();
            $table->string('cta_url', 255)->nullable();

            // Array of product UUIDs, admin-picked order preserved —
            // same convention as extra.mega_menu.new_products_ids.
            $table->jsonb('product_ids')->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
