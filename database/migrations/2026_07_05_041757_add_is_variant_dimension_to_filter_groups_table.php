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
        Schema::table('filter_groups', function (Blueprint $table) {
            // Đánh dấu nhóm nào (VD: Color, Size) được dùng làm dimension để
            // VariantGeneratorService sinh cartesian ra ProductVariant. Nhóm
            // facet thuần (VD: Material) để false, không xuất hiện ở tab Variants.
            $table->boolean('is_variant_dimension')->default(false)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('filter_groups', function (Blueprint $table) {
            $table->dropColumn('is_variant_dimension');
        });
    }
};
