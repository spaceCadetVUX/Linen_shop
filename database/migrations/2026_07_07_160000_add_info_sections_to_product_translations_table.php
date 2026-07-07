<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            // Array of {title, content} pairs — content is Filament RichEditor
            // Tiptap JSON. Renders as dynamic accordions on the product page,
            // replacing the previously hardcoded "Material & Composition" /
            // "Care instructions" / "Shipping & Returns" sections.
            $table->jsonb('info_sections')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('product_translations', function (Blueprint $table) {
            $table->dropColumn('info_sections');
        });
    }
};
