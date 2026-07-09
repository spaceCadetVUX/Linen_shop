<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();

            // Guest-first, same ownership pattern as carts (0010_create_carts_table):
            // user_id when logged in, session_id (client-generated UUID in
            // localStorage) otherwise — no storefront login page exists yet,
            // so session_id is the only path in practice today.
            $table->uuid('user_id')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->uuid('product_id');

            $table->timestamps();

            // NULL != NULL in Postgres unique constraints, so these two only
            // actually enforce dedup within whichever ownership column is set —
            // exactly what's wanted (a guest row and a user row never collide).
            $table->unique(['user_id', 'product_id']);
            $table->unique(['session_id', 'product_id']);

            $table->index('user_id');
            $table->index('session_id');
            $table->index('product_id');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
