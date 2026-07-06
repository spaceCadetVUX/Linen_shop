<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('size_guides', function (Blueprint $table) {
            $table->id();

            // Internal identifier — e.g. 'ao-nu', 'quan-nu', 'dam'
            $table->string('key', 100)->unique();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('size_guide_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('size_guide_id')
                ->constrained('size_guides')
                ->cascadeOnDelete();

            $table->string('locale', 5);

            $table->string('name');          // "Áo nữ" / "Women's Tops"
            $table->text('body')->nullable(); // rich HTML — size table + notes

            $table->timestamps();

            $table->unique(['size_guide_id', 'locale']);
            $table->index('locale');
            $table->index('size_guide_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('size_guide_id')
                ->nullable()
                ->constrained('size_guides')
                ->nullOnDelete();

            $table->index('size_guide_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('size_guide_id');
        });

        Schema::dropIfExists('size_guide_translations');
        Schema::dropIfExists('size_guides');
    }
};
