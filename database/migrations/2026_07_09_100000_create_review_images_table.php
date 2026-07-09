<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('review_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('path', 500);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('review_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_images');
    }
};
