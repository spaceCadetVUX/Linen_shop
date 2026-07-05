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
        // ProductOptionType/Value duplicated FilterGroup/FilterValue (per-product
        // free-text vs. global i18n+slug+color) with no link between them — admin
        // had to declare "Color: Red" twice. Neither table has any data or other
        // dependents (confirmed via GitNexus impact + grep before this migration
        // was written), so this is a clean cut, not a data migration.
        Schema::dropIfExists('product_variant_options');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_option_types');

        // Pivot: one row per (variant, filter_value) pair. A variant with
        // Color=Red + Size=M has 2 rows in this table. filter_value_id now
        // points at the same FilterValue used for storefront facet filtering.
        Schema::create('product_variant_options', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('variant_id');
            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('filter_value_id');
            $table->foreign('filter_value_id')
                ->references('id')
                ->on('filter_values')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['variant_id', 'filter_value_id']);
            $table->index('variant_id');
            $table->index('filter_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally one-way beyond dropping the new pivot: the old
        // product_option_types/values tables carried no data (pre-launch
        // feature), so there is nothing to restore.
        Schema::dropIfExists('product_variant_options');
    }
};
