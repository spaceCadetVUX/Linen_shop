<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_inquiries', function (Blueprint $table) {
            $table->id();

            // Guest-session-first, same ownership pattern as carts/wishlists —
            // no storefront login exists yet.
            $table->uuid('user_id')->nullable();
            $table->string('session_id', 255)->nullable();

            $table->string('name', 100);
            $table->string('phone', 30);
            $table->string('email', 255)->nullable();

            // Cart summary (product/qty/price) built server-side from the
            // resolved cart at submit time, never trusted from the client —
            // plus whatever the customer edited/added in the message box.
            $table->text('message');

            $table->enum('channel', ['zalo', 'phone', 'email'])->default('email');
            $table->enum('status', ['new', 'contacted', 'closed'])->default('new');

            $table->timestamps();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('status');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_inquiries');
    }
};
